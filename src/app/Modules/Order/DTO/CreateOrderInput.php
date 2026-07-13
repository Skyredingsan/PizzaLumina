<?php

declare(strict_types=1);

namespace App\Modules\Order\DTO;

use App\Modules\Order\Enums\DeliveryMethod;

final readonly class CreateOrderInput
{
    public function __construct(
        public DeliveryMethod $deliveryMethod,
        public ?Address $address,
    ) {
    }
}
