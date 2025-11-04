<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'items'];

    protected $casts = [
        'items' => 'array'
    ];

    /**
     * Get the user that owns the cart.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items in the cart as a collection
     */
    public function items()
    {
        return collect($this->items ?? []);
    }

    /**
     * Get the count of items in the cart
     */
    public function getItemsCountAttribute()
    {
        return $this->items() ? $this->items()->count() : 0;
    }

    /**
     * Get total quantity of all items
     */
    public function getTotalQuantityAttribute()
    {
        return $this->items()->sum('quantity');
    }
}
