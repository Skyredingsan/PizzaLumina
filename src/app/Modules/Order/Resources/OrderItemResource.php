<?php

declare(strict_types=1);

namespace App\Modules\Order\Resources;

use App\Modules\Order\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OrderItem $item */
        $item = $this->resource;

        return [
            'id' => $item->id,
            'order_id' => $item->order_id,
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'product_category' => $item->product_category,
            'product_price' => (float) $item->product_price,
            'quantity' => $item->quantity,
            'line_total' => (float) $item->line_total,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
