<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use App\Modules\Order\Enums\OrderStatus;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class InvalidOrderTransitionException extends ConflictHttpException
{
    public static function forTransition(OrderStatus $from, OrderStatus $to): self
    {
        return new self(trans(
            key: 'order.invalid_transition',
            replace: ['from' => $from->value, 'to' => $to->value],
        ));
    }
}
