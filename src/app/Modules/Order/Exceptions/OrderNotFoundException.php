<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use RuntimeException;

final class OrderNotFoundException extends RuntimeException
{
    public static function forOrder(int $orderId): self
    {
        return new self("Заказ #{$orderId} не найден.");
    }
}
