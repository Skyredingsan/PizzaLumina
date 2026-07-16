<?php

declare(strict_types=1);

namespace App\Modules\Cart\Exceptions;

use App\Modules\Product\Enums\ProductCategory;
use RuntimeException;

final class CartLimitExceededException extends RuntimeException
{
    public static function forCategory(ProductCategory $category, int $limit, int $attempted): self
    {
        return new self(trans(
            key: "cart.limit_exceeded.{$category->value}",
            replace: ['limit' => $limit, 'attempted' => $attempted],
        ));
    }
}
