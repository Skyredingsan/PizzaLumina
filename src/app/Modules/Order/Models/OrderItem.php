<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Modules\Product\Enums\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property string $product_name
 * @property ProductCategory $product_category
 * @property int $unit_price
 * @property int $quantity
 * @property string $product_price
 * @property string $line_total
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrderItem extends Model
{
    protected $table = 'order_items';

    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_category',
        'unit_price',
        'quantity',
    ];

    /** @var array<string, class-string|string> */
    protected $casts = [
        'product_category' => ProductCategory::class,
        'unit_price' => 'integer',
        'quantity' => 'integer',
    ];

    /**
     * @return BelongsTo<Order, covariant $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(related: Order::class);
    }

    protected function getProductPriceAttribute(): string
    {
        return number_format(num: $this->unit_price / 100, decimals: 2, thousands_separator: '');
    }

    protected function getLineTotalAttribute(): string
    {
        return number_format(num: ($this->unit_price * $this->quantity) / 100, decimals: 2, thousands_separator: '');
    }
}
