<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use App\Modules\Order\Enums\OrderStatus;
use RuntimeException;

final class InvalidOrderTransitionException extends RuntimeException
{
    public static function forTransition(OrderStatus $from, OrderStatus $to): self
    {
        return new self(
            "Недопустимый переход статуса заказа: {$from->value} → {$to->value}."
        );
    }
}
