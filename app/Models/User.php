<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'profile_image',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the cart associated with the user.
     */
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(UserDeviceToken::class);
    }

    public function addDeviceToken($token, $deviceType = null, $timestamp = null)
    {
        return $this->deviceTokens()->updateOrCreate(
            ['token' => $token],
            [
                'device_type' => $deviceType,
                'last_used_at' => $timestamp ?? now(),
                'registered_at' => now()
            ]
        );
    }

    public function removeDeviceToken($token)
    {
        return $this->deviceTokens()->where('token', $token)->delete();
    }

    public function updateTokenTimestamp($token)
    {
        return $this->deviceTokens()
            ->where('token', $token)
            ->update(['last_used_at' => now()]);
    }

    public function removeStaleTokens()
    {
        $cutoffDate = now()->subDays(30);
        return $this->deviceTokens()->where('last_used_at', '<', $cutoffDate)->delete();
    }

    public function getActiveTokens()
    {
        return $this->deviceTokens()
            ->where('last_used_at', '>=', now()->subDays(30))
            ->pluck('token')
            ->toArray();
    }
}
