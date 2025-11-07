<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * Payment status constants
     */
    const STATUS_PENDING = 'Pending';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_CANCELLED = 'Cancelled';
    const STATUS_REFUNDED = 'Refunded';

    /**
     * Get all available payment statuses
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'billing_address',
        'shipping_address',
        'payment_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    /**
     * Get the user who made the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the orders associated with this payment.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the products from orders with full product details
     * This maintains backward compatibility with the old getPurchasedProductsAttribute
     */
    public function getPurchasedProductsAttribute()
    {
        return $this->orders->map(function ($order) {
            $product = $order->product;

            return (object) [
                'product_id' => $order->product_id,
                'name' => $product?->name ?? $order->product_name,
                'price' => $order->price,
                'quantity' => $order->quantity,
                'subtotal' => $order->subtotal,
                'category_id' => $order->category_id,
                'purchased_at' => $order->purchased_at?->toDateTimeString(),
                'product' => $product,
                'image' => $product?->image,
                'category' => $product && $product->category ? $product->category->name : 'Unknown',
            ];
        });
    }

    /**
     * Backward compatibility: get purchased_items as array (for existing code)
     */
    public function getPurchasedItemsAttribute()
    {
        return $this->orders->map(function ($order) {
            return [
                'product_id' => $order->product_id,
                'name' => $order->product_name,
                'price' => (float) $order->price,
                'quantity' => $order->quantity,
                'subtotal' => (float) $order->subtotal,
                'category_id' => $order->category_id,
                'purchased_at' => $order->purchased_at?->toDateTimeString(),
            ];
        })->toArray();
    }
}
