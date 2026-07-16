<?php

declare(strict_types=1);

namespace App\Modules\Product\Enums;

enum ProductCategory: string
{
    case Pizza = 'pizza';
    case Drink = 'drink';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(array: self::cases(), column_key: 'value');
    }

    public function cartLimit(): int
    {
        return match ($this) {
            self::Pizza => 10,
            self::Drink => 20,
        };
    }
}
