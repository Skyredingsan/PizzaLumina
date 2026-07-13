<?php

declare(strict_types=1);

namespace App\Modules\Order\Resources;

use App\Modules\Order\Enums\DeliveryMethod;
use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status->value,
            'total_amount' => (int) round(num: $this->total_amount / 100),
            'delivery_method' => $this->delivery_method instanceof DeliveryMethod
                ? $this->delivery_method->value
                : $this->delivery_method,
            'address' => [
                'region' => $this->address_region,
                'city' => $this->address_city,
                'street' => $this->address_street,
                'building' => $this->address_building,
                'entrance' => $this->address_entrance,
                'apartment' => $this->address_apartment,
                'zip' => $this->address_zip,
            ],
            'paid_at' => $this->paid_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'items' => OrderItemResource::collection(resource: $this->whenLoaded(relationship: 'items')),
        ];
    }
}
