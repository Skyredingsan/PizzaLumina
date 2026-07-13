<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final class InvalidStatusTransitionException extends ConflictHttpException
{
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct(
            message: $message ?: trans(key: 'order.invalid_transition'),
            previous: $previous,
            code: 0,
        );
    }
}
