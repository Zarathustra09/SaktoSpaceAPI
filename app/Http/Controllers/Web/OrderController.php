<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\UserDeviceToken;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Exception\MessagingException;

class OrderController extends Controller
{
    /**
     * Display a listing of orders with tracking
     */
    public function index(Request $request): View
    {
        $query = Order::with(['payment.user', 'product', 'category']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->whereHas('payment', function($q) use ($request) {
                $q->where('status', $request->payment_status);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('payment', function($subQ) use ($search) {
                      $subQ->where('transaction_id', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('payment.user', function($subQ) use ($search) {
                      $subQ->where('name', 'LIKE', "%{$search}%")
                           ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get filter options
        $orderStatuses = Order::getStatuses();
        $paymentStatuses = Payment::getStatuses();
        $categories = Category::all();

        // Get statistics
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', Order::STATUS_PREPARING)->count(),
            'in_transit_orders' => Order::where('status', Order::STATUS_IN_TRANSIT)->count(),
            'delivered_orders' => Order::where('status', Order::STATUS_DELIVERED)->count(),
            'cancelled_orders' => Order::where('status', Order::STATUS_CANCELLED)->count(),
        ];

        return view('orders.index', compact(
            'orders',
            'orderStatuses',
            'paymentStatuses',
            'categories',
            'stats'
        ));
    }

    /**
     * Display the specified order with full tracking details
     */
    public function show(Order $order): View
    {
        $order->load(['payment.user', 'product', 'category']);

        // Create status tracking timeline
        $statusTimeline = $this->getStatusTimeline($order);

        return view('orders.show', compact('order', 'statusTimeline'));
    }

    /**
     * Update order status with notification
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:' . implode(',', Order::getStatuses()),
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStatus = $order->status;

        $order->update([
            'status' => $request->status,
            'status_updated_at' => now(),
        ]);

        // Log status change
        Log::info('Order status updated', [
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'admin_note' => $request->note,
            'updated_by' => auth()->id(),
        ]);

        $order->load(['payment.user', 'product']);

        // Send notification to customer
        $this->sendOrderStatusNotification($order, $request->note);

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
     * Bulk update order statuses
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
            'status' => 'required|string|in:' . implode(',', Order::getStatuses()),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $orders = Order::whereIn('id', $request->order_ids)->get();
        $updated = 0;

        foreach ($orders as $order) {
            $oldStatus = $order->status;

            $order->update([
                'status' => $request->status,
                'status_updated_at' => now(),
            ]);

            // Send notification
            $this->sendOrderStatusNotification($order);
            $updated++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$updated} orders updated successfully.",
        ]);
    }

    /**
     * Get order statistics for dashboard
     */
    public function getStats(): JsonResponse
    {
        $stats = [
            'total_orders' => Order::count(),
            'orders_by_status' => [],
            'recent_orders' => Order::with(['payment.user', 'product'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'top_products' => Order::selectRaw('product_id, product_name, COUNT(*) as order_count, SUM(quantity) as total_quantity')
                ->groupBy('product_id', 'product_name')
                ->orderBy('order_count', 'desc')
                ->limit(5)
                ->get(),
        ];

        foreach (Order::getStatuses() as $status) {
            $stats['orders_by_status'][$status] = Order::where('status', $status)->count();
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Generate status timeline for order tracking
     */
    private function getStatusTimeline(Order $order): array
    {
        $statuses = Order::getStatuses();
        $currentStatus = $order->status;
        $timeline = [];

        foreach ($statuses as $index => $status) {
            $isCompleted = false;
            $isCurrent = false;
            $timestamp = null;

            if ($status === $currentStatus) {
                $isCompleted = true;
                $isCurrent = true;
                $timestamp = $order->status_updated_at ?? $order->created_at;
            } elseif (array_search($status, $statuses) < array_search($currentStatus, $statuses)) {
                $isCompleted = true;
                // For past statuses, we'll use estimated timestamps
                if ($status === Order::STATUS_PREPARING) {
                    $timestamp = $order->created_at;
                }
            }

            // Skip cancelled status in normal flow unless it's the current status
            if ($status === Order::STATUS_CANCELLED && $currentStatus !== Order::STATUS_CANCELLED) {
                continue;
            }

            $timeline[] = [
                'status' => $status,
                'label' => $this->getStatusLabel($status),
                'description' => $this->getStatusDescription($status),
                'icon' => $this->getStatusIcon($status),
                'is_completed' => $isCompleted,
                'is_current' => $isCurrent,
                'timestamp' => $timestamp,
            ];
        }

        return $timeline;
    }

    /**
     * Get human-readable status labels
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            Order::STATUS_PREPARING => 'Order Confirmed',
            Order::STATUS_TO_SHIP => 'Ready to Ship',
            Order::STATUS_IN_TRANSIT => 'In Transit',
            Order::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
            Order::STATUS_DELIVERED => 'Delivered',
            Order::STATUS_CANCELLED => 'Cancelled',
            default => $status,
        };
    }

    /**
     * Get status descriptions
     */
    private function getStatusDescription(string $status): string
    {
        return match($status) {
            Order::STATUS_PREPARING => 'Your furniture order has been confirmed and is being prepared',
            Order::STATUS_TO_SHIP => 'Your furniture is ready and will be shipped soon',
            Order::STATUS_IN_TRANSIT => 'Your furniture is on its way to you',
            Order::STATUS_OUT_FOR_DELIVERY => 'Your furniture is out for delivery today',
            Order::STATUS_DELIVERED => 'Your furniture has been delivered successfully',
            Order::STATUS_CANCELLED => 'This order has been cancelled',
            default => '',
        };
    }

    /**
     * Get status icons
     */
    private function getStatusIcon(string $status): string
    {
        return match($status) {
            Order::STATUS_PREPARING => 'fas fa-hammer',
            Order::STATUS_TO_SHIP => 'fas fa-box',
            Order::STATUS_IN_TRANSIT => 'fas fa-truck',
            Order::STATUS_OUT_FOR_DELIVERY => 'fas fa-shipping-fast',
            Order::STATUS_DELIVERED => 'fas fa-check-circle',
            Order::STATUS_CANCELLED => 'fas fa-times-circle',
            default => 'fas fa-circle',
        };
    }

    /**
     * Send Firebase notification for order status update
     */
    private function sendOrderStatusNotification(Order $order, string $note = null): void
    {
        try {
            $user = $order->payment->user;
            $tokens = $user ? $user->getActiveTokens() : [];

            if (empty($tokens)) {
                Log::info('No active device tokens for order status notification', ['order_id' => $order->id]);
                return;
            }

            $statusLabel = $this->getStatusLabel($order->status);
            $body = "Your furniture order '{$order->product_name}' is now {$statusLabel}";
            if ($note) {
                $body .= ". Note: {$note}";
            }

            $notification = Notification::create(
                'Order Status Updated',
                $body
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
                    'status_label' => $statusLabel,
                    'product_name' => $order->product_name,
                    'product_id' => (string) $order->product_id,
                    'transaction_id' => $order->payment->transaction_id,
                    'note' => $note ?? '',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'updated_at' => $order->status_updated_at?->toISOString() ?? now()->toISOString(),
                ]);

            $messaging = Firebase::messaging();
            $report = $messaging->sendMulticast($message, $tokens);

            Log::info('Order status notification sent', [
                'order_id' => $order->id,
                'payment_id' => $order->payment_id,
                'status' => $order->status,
                'transaction_id' => $order->payment->transaction_id,
                'successful_sends' => $report->successes()->count(),
                'failed_sends' => $report->failures()->count(),
                'total_tokens' => count($tokens)
            ]);

            // Touch successful tokens to mark them as recently used
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

                    Log::warning('FCM send failure for order status notification', [
                        'order_id' => $order->id,
                        'token' => substr($target->value(), 0, 20) . '...',
                        'error' => $errorMessage
                    ]);

                    // Remove invalid tokens
                    if (strpos($errorMessage, 'INVALID_ARGUMENT') !== false ||
                        strpos($errorMessage, 'UNREGISTERED') !== false ||
                        strpos($errorMessage, 'NOT_FOUND') !== false ||
                        strpos($errorMessage, 'invalid registration token') !== false ||
                        strpos($errorMessage, 'registration token is not a valid FCM') !== false) {

                        $deletedCount = UserDeviceToken::where('token', $target->value())->delete();

                        Log::info('Removed invalid FCM token from order notification', [
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
