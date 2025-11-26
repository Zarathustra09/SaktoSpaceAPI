<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PromotionalAdvertisement;
use Illuminate\Support\Facades\Log;

class PromotionalAdvertisementController extends Controller
{
    public function index()
    {
        $startedAt = microtime(true);
        $ctx = [
            'user_id' => optional(request()->user())->id,
            'ip' => request()->ip(),
            'all' => request()->boolean('all'),
        ];

        try {
            Log::info('[PromotionalAds][INDEX] Request received', $ctx);

            $query = PromotionalAdvertisement::query();
            if (! $ctx['all']) {
                $query->active();
            }

            $adsCollection = $query
                ->orderBy('start_date')
                ->orderBy('end_date')
                ->orderByDesc('created_at')
                ->get();

            $ads = $adsCollection->map(fn($ad) => [
                'id' => $ad->id,
                'title' => $ad->title,
                'description' => $ad->description,
                'image_url' => $ad->image ? asset('storage/'.$ad->image) : null,
                'start_date' => $ad->start_date,
                'end_date' => $ad->end_date,
                'fallback' => false,
            ]);

            $usingFallback = false;
            if ($ads->isEmpty()) {
                $ads = collect(PromotionalAdvertisement::fallback());
                $usingFallback = true;
            }

            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
            Log::info('[PromotionalAds][INDEX] Responding', [
                'count' => $ads->count(),
                'fallback' => $usingFallback,
                'duration_ms' => $durationMs,
            ] + $ctx);

            return response()->json([
                'success' => true,
                'count' => $ads->count(),
                'data' => $ads,
            ]);
        } catch (\Throwable $e) {
            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
            Log::error('[PromotionalAds][INDEX] Error', [
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ] + $ctx);
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function show($id)
    {
        $startedAt = microtime(true);
        $ctx = [
            'user_id' => optional(request()->user())->id,
            'ip' => request()->ip(),
            'ad_id' => $id,
        ];

        try {
            Log::info('[PromotionalAds][SHOW] Request received', $ctx);

            $ad = PromotionalAdvertisement::find($id);
            if (! $ad) {
                Log::warning('[PromotionalAds][SHOW] Not found', $ctx + ['reason' => 'missing']);
                return response()->json(['success' => false, 'message' => 'Not found'], 404);
            }

            if (! $ad->isValid()) {
                Log::warning('[PromotionalAds][SHOW] Not valid for display', $ctx + [
                    'start_date' => $ad->start_date,
                    'end_date' => $ad->end_date,
                ]);
                return response()->json(['success' => false, 'message' => 'Not found'], 404);
            }

            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
            Log::info('[PromotionalAds][SHOW] Responding', $ctx + ['duration_ms' => $durationMs]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'description' => $ad->description,
                    'image_url' => $ad->image ? asset('storage/'.$ad->image) : null,
                    'start_date' => $ad->start_date,
                    'end_date' => $ad->end_date,
                ],
            ]);
        } catch (\Throwable $e) {
            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
            Log::error('[PromotionalAds][SHOW] Error', $ctx + [
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
