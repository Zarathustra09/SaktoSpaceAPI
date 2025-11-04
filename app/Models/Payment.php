<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

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
        'purchased_items',
        'billing_address',
        'shipping_address',
        'payment_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
// In app/Models/Payment.php
    protected $casts = [
        'amount' => 'decimal:2',
        'purchased_items' => 'array',
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
     * Get the order associated with this payment.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the products from purchased_items with full product details
     */
    public function getPurchasedProductsAttribute()
    {
        if (!$this->purchased_items || !is_array($this->purchased_items)) {
            return collect();
        }

        return collect($this->purchased_items)->map(function ($item) {
            $productId = $item['product_id'] ?? null;
            $product = $productId ? Product::with('category')->find($productId) : null;

            $price = isset($item['price']) ? (float) $item['price'] : 0.0;
            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 1;

            return (object) [
                'product_id' => $productId,
                // Prefer DB product data for consistency with products.index
                'name' => $product?->name ?? ($item['name'] ?? 'Unknown Product'),
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => isset($item['subtotal']) ? (float) $item['subtotal'] : $price * $quantity,
                'category_id' => $item['category_id'] ?? null,
                'purchased_at' => $item['purchased_at'] ?? null,
                'product' => $product,
                'image' => $product?->image, // already stored as /storage/images/...
                'category' => $product && $product->category ? $product->category->name : 'Unknown',
            ];
        });
    }
}
