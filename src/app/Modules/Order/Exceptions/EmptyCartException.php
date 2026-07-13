<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use RuntimeException;

final class EmptyCartException extends RuntimeException
{
    public static function forUser(int $userId): self
    {
        return new self(trans(key: 'cart.empty'));
    }
}
