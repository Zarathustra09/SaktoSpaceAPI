<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use App\Models\UserDeviceToken;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Exception\MessagingException;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Display a listing of the payments (with optional sales period filter).
     */
    public function index(Request $request): View
    {
        $period = $request->query('period'); // daily | weekly | monthly | yearly | null
        $rangeStart = null;

        switch ($period) {
            case 'daily':
                $rangeStart = Carbon::today();
                break;
            case 'weekly':
                $rangeStart = Carbon::now()->startOfWeek();
                break;
            case 'monthly':
                $rangeStart = Carbon::now()->startOfMonth();
                break;
            case 'yearly':
                $rangeStart = Carbon::now()->startOfYear();
                break;
            default:
                $period = null;
        }

        $query = Payment::with(['user', 'orders'])->orderBy('payment_date', 'desc');
        if ($rangeStart) {
            $query->where('payment_date', '>=', $rangeStart);
        }

        $payments = $query->get();

        // Aggregate sales metrics
        $allOrders = $payments->flatMap(fn($p) => $p->orders);
        $summary = [
            'total_payments' => $payments->count(),
            'total_items_sold' => (int) $allOrders->sum('quantity'),
            'gross_items_revenue' => (float) $allOrders->sum('subtotal'),
            'total_payment_amount' => (float) $payments->sum('amount'), // may include shipping
            'total_shipping_fees' => (float) $payments->sum('shipping_fee'),
            'average_order_value' => $payments->count() ? round($payments->sum('amount') / $payments->count(), 2) : 0.0,
            'period_label' => $period ? ucfirst($period) : 'All Time',
        ];

        $rangeEnd = Carbon::now();

        return view('payments.index', compact('payments', 'period', 'rangeStart', 'rangeEnd', 'summary'));
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['user', 'orders.product.category', 'orders.category']);
        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Update the specified payment status.
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:' . implode(',', Payment::getStatuses()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->update([
            'status' => $request->status
        ]);

        $payment->load('user');

        // Send FCM notification about order status update
        $this->sendOrderStatusNotification($payment);

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully.',
            'data' => $payment
        ]);
    }

    /**
     * Update order status (for admin panel)
     */
    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:' . implode(',', Order::getStatuses()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order->update([
            'status' => $request->status,
            'status_updated_at' => now(),
        ]);

        $order->load(['payment.user', 'product']);

        // Send FCM notification about order status update
        $this->sendOrderStatusUpdateNotification($order);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
                'status_updated_at' => $order->status_updated_at,
                'product_name' => $order->product_name,
                'transaction_id' => $order->payment->transaction_id,
            ]
        ]);
    }

    /**
     * Export payments to Excel (respects optional ?status= filter).
     */
    public function export(Request $request)
    {
        $status = $request->query('status');
        $period = $request->query('period'); // daily|weekly|monthly|yearly|null
        $rangeStart = null;

        switch ($period) {
            case 'daily':
                $rangeStart = Carbon::today();
                break;
            case 'weekly':
                $rangeStart = Carbon::now()->startOfWeek();
                break;
            case 'monthly':
                $rangeStart = Carbon::now()->startOfMonth();
                break;
            case 'yearly':
                $rangeStart = Carbon::now()->startOfYear();
                break;
            default:
                $period = null;
        }

        $query = Payment::with(['user', 'orders'])->orderBy('payment_date', 'desc');
        if (!empty($status)) {
            $query->where('status', $status);
        }
        if ($rangeStart) {
            $query->where('payment_date', '>=', $rangeStart);
        }
        $payments = $query->get();

        // Sales summary
        $allOrders = $payments->flatMap(fn($p) => $p->orders);
        $summary = [
            'period_label' => $period ? ucfirst($period) : 'All Time',
            'range_start' => $rangeStart,
            'range_end' => Carbon::now(),
            'total_payments' => $payments->count(),
            'total_items_sold' => (int) $allOrders->sum('quantity'),
            'gross_items_revenue' => (float) $allOrders->sum('subtotal'),
            'total_payment_amount' => (float) $payments->sum('amount'),
            'total_shipping_fees' => (float) $payments->sum('shipping_fee'),
            'average_order_value' => $payments->count() ? round($payments->sum('amount') / $payments->count(), 2) : 0.0,
        ];

        // Multi-sheet export (Summary + Payments)
        $export = new class($summary, $payments) implements
            \Maatwebsite\Excel\Concerns\WithMultipleSheets
        {
            private $summary;
            private $payments;
            public function __construct($summary, $payments)
            {
                $this->summary = $summary;
                $this->payments = $payments;
            }
            public function sheets(): array
            {
                $summarySheet = new class($this->summary) implements
                    \Maatwebsite\Excel\Concerns\FromArray,
                    \Maatwebsite\Excel\Concerns\WithTitle,
                    \Maatwebsite\Excel\Concerns\ShouldAutoSize,
                    \Maatwebsite\Excel\Concerns\WithStyles
                {
                    private $summary;
                    public function __construct($summary) { $this->summary = $summary; }
                    public function array(): array
                    {
                        return [
                            ['Sales Summary'],
                            ['Period', $this->summary['period_label']],
                            ['Date Range Start', $this->summary['range_start'] ? $this->summary['range_start']->format('Y-m-d H:i:s') : 'N/A'],
                            ['Date Range End', $this->summary['range_end']->format('Y-m-d H:i:s')],
                            ['Total Payments', $this->summary['total_payments']],
                            ['Total Items Sold', $this->summary['total_items_sold']],
                            ['Gross Items Revenue', $this->summary['gross_items_revenue']],
                            ['Total Payment Amount', $this->summary['total_payment_amount']],
                            ['Total Shipping Fees', $this->summary['total_shipping_fees']],
                            ['Average Order Value', $this->summary['average_order_value']],
                        ];
                    }
                    public function title(): string { return 'Summary'; }
                    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
                    {
                        return [
                            1 => ['font' => ['bold' => true, 'size' => 14]],
                            2 => ['font' => ['bold' => true]],
                        ];
                    }
                };

                $paymentsSheet = new class($this->payments) implements
                    \Maatwebsite\Excel\Concerns\FromCollection,
                    \Maatwebsite\Excel\Concerns\WithHeadings,
                    \Maatwebsite\Excel\Concerns\WithMapping,
                    \Maatwebsite\Excel\Concerns\ShouldAutoSize,
                    \Maatwebsite\Excel\Concerns\WithStyles,
                    \Maatwebsite\Excel\Concerns\WithTitle
                {
                    private $rows;
                    public function __construct($rows) { $this->rows = $rows; }
                    public function collection() { return $this->rows; }
                    public function headings(): array
                    {
                        return [
                            'Transaction ID',
                            'User Name',
                            'User Email',
                            'Amount',
                            'Payment Method',
                            'Status',
                            'Items Count',
                            'Shipping Fee',
                            'Payment Date',
                            'Created At',
                            'Updated At',
                        ];
                    }
                    public function map($payment): array
                    {
                        return [
                            $payment->transaction_id,
                            optional($payment->user)->name ?? 'N/A',
                            optional($payment->user)->email ?? 'N/A',
                            (string) $payment->amount,
                            (string) $payment->payment_method,
                            (string) $payment->status,
                            $payment->orders->count(),
                            (string) $payment->shipping_fee,
                            $payment->payment_date?->format('Y-m-d H:i:s') ?? '',
                            $payment->created_at?->format('Y-m-d H:i:s') ?? '',
                            $payment->updated_at?->format('Y-m-d H:i:s') ?? '',
                        ];
                    }
                    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
                    {
                        return [1 => ['font' => ['bold' => true]]];
                    }
                    public function title(): string { return 'Payments'; }
                };

                return [$summarySheet, $paymentsSheet];
            }
        };

        $filename = 'payments_report' .
            ($status ? "_status-{$status}" : '') .
            ($period ? "_period-{$period}" : '') .
            '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download($export, $filename);
    }

    /**
     * Send a Firebase notification to the payment's user about status change.
     */
    private function sendOrderStatusNotification(Payment $payment): void
    {
        try {
            $user = $payment->user;
            $tokens = $user ? $user->getActiveTokens() : [];

            if (empty($tokens)) {
                Log::info('No active device tokens for payment notification', ['payment_id' => $payment->id]);
                return;
            }

            $shortBody = "Order #{$payment->id} status changed to {$payment->status}.";

            $notification = Notification::create(
                'Payment Status Updated',
                $shortBody
            );

            $androidConfig = AndroidConfig::fromArray([
                'ttl' => '3600s',
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'tag' => 'order_status',
                ],
                'fcm_options' => [
                    'analytics_label' => 'order_status',
                ],
            ]);

            $apnsConfig = ApnsConfig::fromArray([
                'headers' => [
                    'apns-push-type' => 'alert',
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'content-available' => 1,
                        'mutable-content' => 1,
                        'category' => 'order_status',
                    ],
                ],
                'fcm_options' => [
                    'analytics_label' => 'order_status',
                ],
            ]);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withAndroidConfig($androidConfig)
                ->withApnsConfig($apnsConfig)
                ->withData([
                    'type' => 'order_status_update',
                    'order_id' => (string) $payment->id,
                    'status' => $payment->status,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'updated_at' => $payment->updated_at?->toISOString() ?? now()->toISOString(),
                ]);

            $messaging = Firebase::messaging();
            $report = $messaging->sendMulticast($message, $tokens);

            Log::info('Order status notification sent', [
                'payment_id' => $payment->id,
                'successful_sends' => $report->successes()->count(),
                'failed_sends' => $report->failures()->count(),
                'total_tokens' => count($tokens)
            ]);

            // Touch successful tokens
            $successTokens = array_map(
                fn($s) => $s->target()->value(),
                $report->successes()->getItems()
            );
            if (!empty($successTokens)) {
                UserDeviceToken::touchTokens($successTokens);
            }

            // Handle failures: log and remove invalid tokens
            if ($report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    $target = $failure->target();
                    $error = $failure->error();
                    $errorMessage = $error ? $error->getMessage() : 'unknown';

                    Log::warning('FCM send failure for order notification', [
                        'payment_id' => $payment->id,
                        'token' => substr($target->value(), 0, 20) . '...',
                        'error' => $errorMessage
                    ]);

                    if (strpos($errorMessage, 'INVALID_ARGUMENT') !== false ||
                        strpos($errorMessage, 'UNREGISTERED') !== false ||
                        strpos($errorMessage, 'NOT_FOUND') !== false ||
                        strpos($errorMessage, 'invalid registration token') !== false ||
                        strpos($errorMessage, 'registration token is not a valid FCM') !== false) {

                        $deletedCount = UserDeviceToken::where('token', $target->value())->delete();

                        Log::info('Removed invalid FCM token', [
                            'token' => substr($target->value(), 0, 20) . '...',
                            'deleted_count' => $deletedCount
                        ]);
                    }
                }
            }
        } catch (MessagingException $e) {
            Log::error('Firebase messaging error for payment notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        } catch (\Exception $e) {
            Log::error('General error sending payment notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send a Firebase notification to the order's user about status change.
     */
    private function sendOrderStatusUpdateNotification(Order $order): void
    {
        try {
            $user = $order->payment->user;
            $tokens = $user ? $user->getActiveTokens() : [];

            if (empty($tokens)) {
                Log::info('No active device tokens for order status notification', ['order_id' => $order->id]);
                return;
            }

            $shortBody = "Your order '{$order->product_name}' status changed to {$order->status}.";

            $notification = Notification::create(
                'Order Status Updated',
                $shortBody
            );

            $androidConfig = AndroidConfig::fromArray([
                'ttl' => '3600s',
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'tag' => 'order_status',
                ],
                'fcm_options' => [
                    'analytics_label' => 'order_status',
                ],
            ]);

            $apnsConfig = ApnsConfig::fromArray([
                'headers' => [
                    'apns-push-type' => 'alert',
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'content-available' => 1,
                        'mutable-content' => 1,
                        'category' => 'order_status',
                    ],
                ],
                'fcm_options' => [
                    'analytics_label' => 'order_status',
                ],
            ]);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withAndroidConfig($androidConfig)
                ->withApnsConfig($apnsConfig)
                ->withData([
                    'type' => 'order_status_update',
                    'order_id' => (string) $order->id,
                    'payment_id' => (string) $order->payment_id,
                    'status' => $order->status,
                    'product_name' => $order->product_name,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'updated_at' => $order->status_updated_at?->toISOString() ?? now()->toISOString(),
                ]);

            $messaging = Firebase::messaging();
            $report = $messaging->sendMulticast($message, $tokens);

            Log::info('Order status notification sent', [
                'order_id' => $order->id,
                'status' => $order->status,
                'successful_sends' => $report->successes()->count(),
                'failed_sends' => $report->failures()->count(),
                'total_tokens' => count($tokens)
            ]);

            // Touch successful tokens
            $successTokens = array_map(
                fn($s) => $s->target()->value(),
                $report->successes()->getItems()
            );
            if (!empty($successTokens)) {
                UserDeviceToken::touchTokens($successTokens);
            }

            // Handle failures: log and remove invalid tokens
            if ($report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    $target = $failure->target();
                    $error = $failure->error();
                    $errorMessage = $error ? $error->getMessage() : 'unknown';

                    Log::warning('FCM send failure for order notification', [
                        'order_id' => $order->id,
                        'token' => substr($target->value(), 0, 20) . '...',
                        'error' => $errorMessage
                    ]);

                    if (strpos($errorMessage, 'INVALID_ARGUMENT') !== false ||
                        strpos($errorMessage, 'UNREGISTERED') !== false ||
                        strpos($errorMessage, 'NOT_FOUND') !== false ||
                        strpos($errorMessage, 'invalid registration token') !== false ||
                        strpos($errorMessage, 'registration token is not a valid FCM') !== false) {

                        $deletedCount = UserDeviceToken::where('token', $target->value())->delete();

                        Log::info('Removed invalid FCM token', [
                            'token' => substr($target->value(), 0, 20) . '...',
                            'deleted_count' => $deletedCount
                        ]);
                    }
                }
            }
        } catch (MessagingException $e) {
            Log::error('Firebase messaging error for order status notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        } catch (\Exception $e) {
            Log::error('General error sending order status notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
