<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionalAdvertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function scopeActive($query)
    {
        $today = now()->toDateString(); // date-only filtering
        return $query
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')
                  ->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhereDate('end_date', '>=', $today);
            });
    }

    public function isValid(): bool
    {
        $today = now()->toDateString(); // date-only validity
        if ($this->start_date && $this->start_date->toDateString() > $today) return false;
        if ($this->end_date && $this->end_date->toDateString() < $today) return false;
        return true;
    }

    /**
     * Fallback ads when none exist in DB.
     */
    public static function fallback(): array
    {
        return [
            [
                'id' => 0,
                'title' => 'Welcome Offer',
                'description' => 'Get started with exclusive discounts.',
                'image_url' => asset('images/fallback/promotions/welcome.png'),
                'start_date' => null,
                'end_date' => null,
                'fallback' => true,
            ],
            [
                'id' => 0,
                'title' => 'Weekly Deals',
                'description' => 'Check out limited-time weekly deals.',
                'image_url' => asset('images/fallback/promotions/weekly.png'),
                'start_date' => null,
                'end_date' => null,
                'fallback' => true,
            ],
        ];
    }
}
