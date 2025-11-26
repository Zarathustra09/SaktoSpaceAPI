<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PromotionalAdvertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\UserDeviceToken;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Exception\MessagingException;

class PromotionalAdvertisementController extends Controller
{
    public function index()
    {
        $ads = PromotionalAdvertisement::orderBy('start_date')->orderBy('end_date')->orderByDesc('created_at')->paginate(10);
        return view('promotional_advertisements.index', compact('ads'));
    }

    public function create()
    {
        return view('promotional_advertisements.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,jpg,png,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('promotional-ads', 'public');
        }

        $ad = PromotionalAdvertisement::create($data);

        // Send blast notification
        $this->sendPromotionBlastNotification($ad);

        return redirect()->route('promotional-advertisements.index')->with('success', 'Created.');
    }

    public function show(PromotionalAdvertisement $promotionalAdvertisement)
    {
        // Return JSON for AJAX requests, view for direct browser access
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'id' => $promotionalAdvertisement->id,
                'title' => $promotionalAdvertisement->title,
                'description' => $promotionalAdvertisement->description,
                'image' => $promotionalAdvertisement->image,
                'start_date' => $promotionalAdvertisement->start_date,
                'end_date' => $promotionalAdvertisement->end_date,
                'created_at' => $promotionalAdvertisement->created_at,
                'updated_at' => $promotionalAdvertisement->updated_at,
            ]);
        }

        return view('promotional_advertisements.show', compact('promotionalAdvertisement'));
    }

    public function edit(PromotionalAdvertisement $promotionalAdvertisement)
    {
        return view('promotional_advertisements.edit', compact('promotionalAdvertisement'));
    }

    public function update(Request $request, PromotionalAdvertisement $promotionalAdvertisement)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($request->hasFile('image')) {
            if ($promotionalAdvertisement->image) {
                Storage::disk('public')->delete($promotionalAdvertisement->image);
            }
            $data['image'] = $request->file('image')->store('promotional-ads', 'public');
        }

        $promotionalAdvertisement->update($data);

        return redirect()->route('promotional-advertisements.index')->with('success', 'Updated.');
    }

    public function destroy(PromotionalAdvertisement $promotionalAdvertisement)
    {
        if ($promotionalAdvertisement->image) {
            Storage::disk('public')->delete($promotionalAdvertisement->image);
        }
        $promotionalAdvertisement->delete();
        return redirect()->route('promotional-advertisements.index')->with('success', 'Deleted.');
    }

    /**
     * Send a blast notification to all active users about new promotion.
     */
    private function sendPromotionBlastNotification(PromotionalAdvertisement $ad): void
    {
        try {
            // Get unique active device tokens
            $tokens = array_values(array_unique(UserDeviceToken::getAllActiveTokens()));

            if (empty($tokens)) {
                Log::info('No active device tokens found for promotion notification');
                return;
            }

            $shortBody = $ad->description
                ? (substr($ad->description, 0, 100) . (strlen($ad->description) > 100 ? '...' : ''))
                : 'Check out our new promotional offer!';

            $notification = Notification::create(
                '🎉 New Promotion: ' . $ad->title,
                $shortBody
            );

            // Configure Android for high priority delivery and tap handling
            $androidConfig = AndroidConfig::fromArray([
                'ttl' => '3600s',
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'tag' => 'promotion',
                    'color' => '#28a745',
                ],
                'fcm_options' => [
                    'analytics_label' => 'promotions',
                ],
            ]);

            // Configure APNs for iOS alert delivery and tap handling
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
                        'category' => 'promotion',
                    ],
                ],
                'fcm_options' => [
                    'analytics_label' => 'promotions',
                ],
            ]);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withAndroidConfig($androidConfig)
                ->withApnsConfig($apnsConfig)
                ->withData([
                    'type' => 'promotion',
                    'promotion_id' => (string) $ad->id,
                    'title' => $ad->title,
                    'body' => $shortBody,
                    'image_url' => $ad->image ? asset('storage/' . $ad->image) : '',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'start_date' => $ad->start_date?->toISOString() ?? '',
                    'end_date' => $ad->end_date?->toISOString() ?? '',
                    'created_at' => $ad->created_at->toISOString(),
                ]);

            $messaging = Firebase::messaging();
            $report = $messaging->sendMulticast($message, $tokens);

            Log::info('Promotion blast notification sent', [
                'promotion_id' => $ad->id,
                'title' => $ad->title,
                'successful_sends' => $report->successes()->count(),
                'failed_sends' => $report->failures()->count(),
                'total_tokens' => count($tokens)
            ]);

            // Touch tokens that received the message successfully
            $successTokens = array_map(
                fn($s) => $s->target()->value(),
                $report->successes()->getItems()
            );
            if (!empty($successTokens)) {
                UserDeviceToken::touchTokens($successTokens);
            }

            // Handle failures and remove invalid tokens
            if ($report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    $target = $failure->target();
                    $error = $failure->error();
                    $errorMessage = $error ? $error->getMessage() : 'unknown';

                    Log::warning('FCM send failure for promotion', [
                        'promotion_id' => $ad->id,
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
                            'error' => $errorMessage,
                            'deleted_count' => $deletedCount
                        ]);
                    }
                }
            }

        } catch (MessagingException $e) {
            Log::error('Firebase messaging error for promotion', [
                'promotion_id' => $ad->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        } catch (\Exception $e) {
            Log::error('General error sending promotion notification', [
                'promotion_id' => $ad->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
