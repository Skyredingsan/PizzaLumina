<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use RuntimeException;

final class CartInvalidException extends RuntimeException
{
    public static function productMissing(int $productId): self
    {
        return new self(trans(
            key: 'order.cart_product_missing',
            replace: ['id' => $productId],
        ));
    }

    public static function productNotAvailable(string $productName): self
    {
        return new self(trans(
            key: 'order.cart_product_unavailable',
            replace: ['name' => $productName],
        ));
    }
}
