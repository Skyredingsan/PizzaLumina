<?php

declare(strict_types=1);

namespace App\Modules\Product\Models;

use App\Modules\Product\Enums\ProductCategory;
use App\Shared\Casts\MoneyCast;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @property int                $id
 * @property string             $name
 * @property string             $description
 * @property Money              $price     Цена (Value Object)
 * @property float              $weight    Вес в граммах
 * @property ProductCategory    $category  Категория (enum)
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

    protected function casts(): array
    {
        return [
            'category' => ProductCategory::class,
            'price' => MoneyCast::class,
        ];
    }

    protected static function newFactory(): Factory
    {
        return \Database\Factories\ProductFactory::new();
    }
}
