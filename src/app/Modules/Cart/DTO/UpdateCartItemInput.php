<?php

declare(strict_types=1);

namespace App\Modules\Cart\DTO;

final readonly class UpdateCartItemInput
{
    public function __construct(
        public int $quantity,
    ) {
    }
}
