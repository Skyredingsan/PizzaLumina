<?php

declare(strict_types=1);

namespace App\Modules\Order\DTO;

final readonly class CreateOrderInput
{
    public function __construct(
        public Address $address,
    ) {
    }
}
