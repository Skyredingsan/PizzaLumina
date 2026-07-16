<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use RuntimeException;

final class OrderTooLargeException extends RuntimeException
{
    public static function forCount(int $maxItems, int $attempted): self
    {
        return new self(trans(
            key: 'order.too_large',
            replace: ['max' => $maxItems, 'count' => $attempted],
        ));
    }
}
