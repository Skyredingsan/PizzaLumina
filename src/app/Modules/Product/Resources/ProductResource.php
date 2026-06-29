<?php

declare(strict_types=1);

namespace App\Modules\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Shared\ValueObjects\Money $price */
        $price = $this->price;

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => [
                'amount'   => $price->getAmount(),
                'rubles'   => $price->getRubles(),
                'currency' => $price->getCurrency(),
            ],
            'weight'      => (float) $this->weight,
            'category'    => $this->category->value,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
