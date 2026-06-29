<?php

declare(strict_types=1);

namespace App\Modules\Product\Enums;

enum ProductCategory: string
{
    case Pizza = 'пицца';
    case Drink = 'напиток';

    /**
     * Возвращает массив значений для валидации.
     * Используется в StoreProductRequest/UpdateProductRequest.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
