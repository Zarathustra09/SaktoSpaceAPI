<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserDeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'last_used_at',
        'registered_at'
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'registered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all active device tokens (updated within the last 30 days).
     *
     * @return array
     */
    public static function getAllActiveTokens(): array
    {
        return self::where('last_used_at', '>=', now()->subDays(30))
            ->pluck('token')
            ->toArray();
    }

    public static function getActiveTokensByDeviceType($deviceType)
    {
        return self::where('device_type', $deviceType)
                   ->where('last_used_at', '>=', now()->subDays(30))
                   ->pluck('token')
                   ->toArray();
    }

    public static function removeExpiredTokens()
    {
        $androidCutoff = now()->subDays(270); // FCM's 270-day expiry for Android
        $generalCutoff = now()->subDays(60);   // General cleanup for other platforms

        return self::where(function($query) use ($androidCutoff, $generalCutoff) {
            $query->where('device_type', 'android')
                  ->where('last_used_at', '<', $androidCutoff);
        })->orWhere(function($query) use ($generalCutoff) {
            $query->whereIn('device_type', ['ios', 'web'])
                  ->where('last_used_at', '<', $generalCutoff);
        })->delete();
    }

    public static function removeStaleTokens()
    {
        $cutoffDate = now()->subDays(30);
        return self::where('last_used_at', '<', $cutoffDate)->delete();
    }

    /**
     * Touch multiple tokens to update their last_used_at timestamp.
     *
     * @param array $tokens
     * @return void
     */
    public static function touchTokens(array $tokens): void
    {
        if (empty($tokens)) {
            return;
        }

        self::whereIn('token', $tokens)
            ->update(['last_used_at' => now()]);
    }
}
