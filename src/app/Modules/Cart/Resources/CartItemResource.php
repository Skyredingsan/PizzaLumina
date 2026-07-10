<?php

declare(strict_types=1);

namespace App\Modules\Cart\Resources;

use App\Modules\Cart\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CartItem $item */
        $item = $this->resource;

        $productData = null;
        if ($item->relationLoaded(key: 'product') && $item->product !== null) {
            $productData = [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'price' => [
                    'amount' => $item->product->price->getAmount(),
                    'rubles' => $item->product->price->getRubles(),
                    'currency' => $item->product->price->getCurrency(),
                ],
                'category' => $item->product->category->value,
            ];
        }

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'product' => $productData,
        ];
    }
}
