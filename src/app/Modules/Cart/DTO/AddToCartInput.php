<?php

declare(strict_types=1);

namespace App\Modules\Cart\DTO;

final readonly class AddToCartInput
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {
    }
}
