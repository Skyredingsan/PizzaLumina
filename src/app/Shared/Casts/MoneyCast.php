<?php

declare(strict_types=1);

namespace App\Shared\Casts;

use App\Shared\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Eloquent-каст для Money value object.
 *
 * @implements CastsAttributes<Money, int|Money|string, Money, int>
 */
final class MoneyCast implements CastsAttributes
{
    /**
     * @param  mixed  $value  То, что лежит в БД (int центов или null)
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::fromCents((int) $value);
    }

    /**
     * @param  mixed  $value  Money | int (центы) | numeric (рубли) | null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->getAmount();
        }

        if (is_int($value)) {
            return $value;
        }


        if (is_numeric($value)) {
            return Money::fromRubles((float) $value)->getAmount();
        }

        throw new InvalidArgumentException(
            'Cannot cast value to Money. Expected Money|int|numeric, got: ' . get_debug_type($value)
        );
    }
}
