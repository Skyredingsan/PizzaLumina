<?php

declare(strict_types=1);

namespace App\Modules\Cart\Exceptions;

use RuntimeException;

final class CartItemNotFoundException extends RuntimeException
{
    public static function forItem(int $itemId): self
    {
        return new self(trans(key: 'cart.item_not_found', replace: ['id' => $itemId]));
    }
}
