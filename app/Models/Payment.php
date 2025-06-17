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
}
