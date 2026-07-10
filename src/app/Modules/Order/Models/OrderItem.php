<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property string $product_name
 * @property string $product_category
 * @property float $product_price
 * @property int $quantity
 * @property float $line_total
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Modules\Order\Models\Order $order
 * @property-read \App\Modules\Product\Models\Product $product
 */
class OrderItem extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_category',
        'product_price',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'product_price' => 'decimal:2',
            'line_total'    => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(related: Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(related: Product::class);
    }
}
