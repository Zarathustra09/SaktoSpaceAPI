<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
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

class PaymentController extends Controller
{
    /**
     * Display a listing of the payments.
     */
    public function index(): View
    {
        $payments = Payment::with('user')->orderBy('payment_date', 'desc')->get();
        return view('payments.index', compact('payments'));
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load('user');
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
            'status' => 'required|string|in:pending,completed,failed,refunded',
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
}
