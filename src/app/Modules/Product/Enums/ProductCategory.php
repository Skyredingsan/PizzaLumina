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
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(array: self::cases(), column_key: 'value');
    }
}
