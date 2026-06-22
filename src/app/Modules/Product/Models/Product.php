<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'weight',
        'category',
    ];

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = (int) round($value * 100);
    }

    public function getPriceAttribute($value)
    {
        return $value / 100;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return \Database\Factories\ProductFactory::new();
    }
}
