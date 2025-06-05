<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
    ];

    /**
     * Category types enum
     */
    const TYPE_FURNITURE = 'furniture';
    const TYPE_DECOR = 'decor';
    const TYPE_LIGHTING = 'lighting';
    const TYPE_OUTDOOR = 'outdoor';

    /**
     * Get all available category types
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_FURNITURE,
            self::TYPE_DECOR,
            self::TYPE_LIGHTING,
            self::TYPE_OUTDOOR,
        ];
    }

    /**
     * Get products belonging to this category
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
