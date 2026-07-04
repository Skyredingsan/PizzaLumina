<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\ValueObjects;

use App\Shared\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    // ─── Создание ────────────────────────────────────────────────────

    public function test_can_create_from_rubles(): void
    {
        $money = Money::fromRubles(1500);

        $this->assertSame(150000, $money->getAmount());
        $this->assertSame(1500.0, $money->getRubles());
        $this->assertSame('RUB', $money->getCurrency());
    }

    public function test_can_create_from_cents(): void
    {
        $money = Money::fromCents(150000);

        $this->assertSame(150000, $money->getAmount());
        $this->assertSame(1500.0, $money->getRubles());
    }

    public function test_can_create_with_fractional_rubles(): void
    {
        $money = Money::fromRubles('1500.99');

        $this->assertSame(150099, $money->getAmount());
        $this->assertSame(1500.99, $money->getRubles());
    }

    public function test_default_currency_is_rub(): void
    {
        $money = Money::fromRubles(100);

        $this->assertSame('RUB', $money->getCurrency());
    }

    public function test_can_specify_custom_currency(): void
    {
        $money = new Money(100, 'USD');

        $this->assertSame('USD', $money->getCurrency());
    }

    public function test_zero_amount_is_valid(): void
    {
        $money = Money::fromCents(0);

        $this->assertTrue($money->isZero());
    }

    // ─── Самовалидация ───────────────────────────────────────────────

    public function test_negative_amount_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be negative');

        new Money(-1);
    }

    public function test_invalid_currency_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('3-letter uppercase ISO 4217');

        new Money(100, 'rubles');
    }

    public function test_lowercase_currency_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Money(100, 'rub');
    }

    public function test_too_short_currency_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Money(100, 'RU');
    }

    // ─── Арифметика ──────────────────────────────────────────────────

    public function test_add_returns_new_instance_with_sum(): void
    {
        $a = Money::fromRubles(100);
        $b = Money::fromRubles(50);

        $result = $a->add($b);

        $this->assertSame(15000, $result->getAmount());  // 150 рублей
        // Иммутабельность: исходные объекты не изменились
        $this->assertSame(10000, $a->getAmount());
        $this->assertSame(5000, $b->getAmount());
    }

    public function test_subtract_returns_difference(): void
    {
        $a = Money::fromRubles(100);
        $b = Money::fromRubles(30);

        $result = $a->subtract($b);

        $this->assertSame(7000, $result->getAmount());  // 70 рублей
    }

    public function test_subtract_to_negative_throws(): void
    {
        $a = Money::fromRubles(10);
        $b = Money::fromRubles(50);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('negative amount');

        $a->subtract($b);
    }

    public function test_multiply_by_integer_quantity(): void
    {
        $price = Money::fromRubles(500);

        $total = $price->multiply(3);  // 3 штуки

        $this->assertSame(150000, $total->getAmount());  // 1500 рублей
    }

    public function test_multiply_by_float_factor(): void
    {
        $price = Money::fromRubles(1000);

        $withTax = $price->multiply(1.2);  // +20%

        $this->assertSame(120000, $withTax->getAmount());  // 1200 рублей
    }

    public function test_add_with_different_currencies_throws(): void
    {
        $rub = new Money(100, 'RUB');
        $usd = new Money(100, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('different currencies');

        $rub->add($usd);
    }

    // ─── Сравнение ───────────────────────────────────────────────────

    public function test_equals_returns_true_for_same_value(): void
    {
        $a = Money::fromRubles(100);
        $b = Money::fromRubles(100);

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_amount(): void
    {
        $a = Money::fromRubles(100);
        $b = Money::fromRubles(200);

        $this->assertFalse($a->equals($b));
    }

    public function test_equals_returns_false_for_different_currency(): void
    {
        $a = new Money(100, 'RUB');
        $b = new Money(100, 'USD');

        $this->assertFalse($a->equals($b));
    }

    public function test_is_greater_than(): void
    {
        $a = Money::fromRubles(200);
        $b = Money::fromRubles(100);

        $this->assertTrue($a->isGreaterThan($b));
        $this->assertFalse($b->isGreaterThan($a));
    }

    public function test_is_less_than(): void
    {
        $a = Money::fromRubles(100);
        $b = Money::fromRubles(200);

        $this->assertTrue($a->isLessThan($b));
        $this->assertFalse($b->isLessThan($a));
    }

    // ─── Строковое представление ────────────────────────────────────

    public function test_to_string_formats_correctly(): void
    {
        $money = Money::fromRubles(1500);

        $this->assertSame('1 500.00 RUB', (string) $money);
    }

    public function test_to_string_with_fractional(): void
    {
        $money = Money::fromRubles('99.99');

        $this->assertSame('99.99 RUB', (string) $money);
    }
}
