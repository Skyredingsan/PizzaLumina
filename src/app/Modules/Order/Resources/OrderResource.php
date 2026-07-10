<?php

declare(strict_types=1);

namespace App\Modules\Order\Resources;

use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'user_id' => $order->user_id,
            'status' => $order->status->value,
            'total_amount' => (float) $order->total_amount,
            'currency' => $order->currency,
            'address' => [
                'region' => $order->address_region,
                'city' => $order->address_city,
                'street' => $order->address_street,
                'building' => $order->address_building,
                'entrance' => $order->address_entrance,
                'apartment' => $order->address_apartment,
                'zip' => $order->address_zip,
            ],
            'items' => OrderItemResource::collection(resource: $order->items),
            'paid_at' => $order->paid_at?->toIso8601String(),
            'completed_at' => $order->completed_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }
}
