<?php

declare(strict_types=1);

namespace App\Modules\Cart\Exceptions;

use App\Modules\Product\Enums\ProductCategory;
use RuntimeException;

final class CartLimitExceededException extends RuntimeException
{
    public static function forCategory(
        ProductCategory $category,
        int $limit,
        int $attempted,
    ): self {
        $categoryLabel = match ($category) {
            ProductCategory::Pizza => 'пицц',
            ProductCategory::Drink => 'напитков',
        };

        return new self(
            "Превышен лимит {$categoryLabel} в корзине. Максимум: {$limit}, попытка: {$attempted}."
        );
    }
}
