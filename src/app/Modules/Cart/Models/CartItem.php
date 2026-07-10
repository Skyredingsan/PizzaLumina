<?php

declare(strict_types=1);

namespace App\Modules\Cart\Models;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $cart_id
 * @property int $product_id
 * @property int $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Modules\Cart\Models\Cart $cart
 * @property-read \App\Modules\Product\Models\Product $product
 */
class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'quantity'];

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(related: Cart::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(related: Product::class);
    }
}
