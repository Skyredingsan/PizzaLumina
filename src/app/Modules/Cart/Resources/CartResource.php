<?php

declare(strict_types=1);

namespace App\Modules\Cart\Resources;

use App\Modules\Cart\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Cart $cart */
        $cart = $this->resource;

        return [
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'items' => CartItemResource::collection(resource: $cart->items),
            'items_count' => $cart->items->count(),
            'total_quantity' => $cart->items->sum(callback: fn ($item) => $item->quantity),
            'created_at' => $cart->created_at?->toIso8601String(),
            'updated_at' => $cart->updated_at?->toIso8601String(),
        ];
    }
}
