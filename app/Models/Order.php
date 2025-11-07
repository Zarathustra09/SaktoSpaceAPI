<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
        'product_name',
        'category_id',
        'purchased_at',
        'status',
        'status_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity' => 'integer',
        'purchased_at' => 'datetime',
        'status_updated_at' => 'datetime',
    ];

    /**
     * Order status constants
     */
    const STATUS_PREPARING = 'Preparing';
    const STATUS_TO_SHIP = 'To Ship';
    const STATUS_IN_TRANSIT = 'In Transit';
    const STATUS_OUT_FOR_DELIVERY = 'Out for Delivery';
    const STATUS_DELIVERED = 'Delivered';
    const STATUS_CANCELLED = 'Cancelled';

    /**
     * Get all available order statuses
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PREPARING,
            self::STATUS_TO_SHIP,
            self::STATUS_IN_TRANSIT,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Get the payment that owns this order.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the product associated with this order.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the category associated with this order.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who made this order through payment.
     */
    public function user()
    {
        return $this->hasOneThrough(User::class, Payment::class, 'id', 'id', 'payment_id', 'user_id');
    }
}
