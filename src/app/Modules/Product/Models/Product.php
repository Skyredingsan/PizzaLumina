<?php

declare (strict_types = 1);

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @property int                $id
 * @property string             $name
 * @property string             $description
 * @property float              $price     Цена в рублях (мутатор конвертирует в int центов при сохранении)
 * @property float              $weight    Вес в граммах
 * @property ProductCategory    $category  Категория (enum, в БД хранится строкой)
 * @property \Carbon\Carbon     $created_at
 * @property \Carbon\Carbon     $updated_at
 */
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

    public function setPriceAttribute(int|float|string $value): void
    {
        $this->attributes['price'] = (int) round((float)$value * 100);
    }

    public function getPriceAttribute($value)
    {
        return $value / 100;
    }

    protected function casts(): array
    {
        return [
            'category' => ProductCategory::class,
        ];
    }
    protected static function newFactory(): Factory
    {
        return \Database\Factories\ProductFactory::new();
    }
}
