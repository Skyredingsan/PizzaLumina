<?php

declare(strict_types=1);

namespace App\Shared\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * Value Object для денег.
 */
final readonly class Money implements Stringable
{
    /**
     * @param  int  $amount  Сумма в центах (минорная единица валюты)
     * @param  string  $currency  ISO 4217 код валюты, 3 заглавные буквы (RUB, USD, EUR)
     */
    public function __construct(
        private int $amount,
        private string $currency = 'RUB',
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException(
                message: 'Money amount cannot be negative. Got: '.$amount
            );
        }

        if (! preg_match(pattern: '/^[A-Z]{3}$/', subject: $currency)) {
            throw new InvalidArgumentException(
                message: 'Currency must be a 3-letter uppercase ISO 4217 code. Got: '.$currency
            );
        }
    }

    /**
     * Создать Money из суммы в рублях (мажорная единица).
     * Принимает int|float|string — то, что обычно приходит из API.
     */
    public static function fromRubles(int|float|string $rubles): self
    {
        return new self((int) round(num: (float) $rubles * 100));
    }

    /**
     * Создать Money из центов (минорная единица).
     * Используется при чтении из БД (где хранятся центы как integer).
     */
    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Возвращает сумму в рублях (float).
     * ВАЖНО: использовать только для отображения пользователю.
     * Для всех вычислений — использовать getAmount() (int центов).
     */
    public function getRubles(): float
    {
        return $this->amount / 100;
    }

    // ─── Арифметика ───────────────────────────────────────────────────
    // Все методы возвращают НОВЫЙ экземпляр — иммутабельность.

    /**
     * Сложение. Оба Money должны быть в одной валюте.
     *
     * @throws InvalidArgumentException При попытке сложить разные валюты.
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency(other: $other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    /**
     * Вычитание.
     *
     * @throws InvalidArgumentException При разных валютах ИЛИ если результат < 0.
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency(other: $other);

        $result = $this->amount - $other->amount;
        if ($result < 0) {
            throw new InvalidArgumentException(
                message: "Money subtraction would result in negative amount: {$this->amount} - {$other->amount}"
            );
        }

        return new self($result, $this->currency);
    }

    /**
     * Умножение на коэффициент. Используется для расчёта "цена × количество".
     *
     *   $price->multiply(3)        // 3 штуки
     *   $price->multiply(1.2)      // +20% (наценка)
     *
     * Округление — банковское (round half up), через round().
     * Для критичных расчётов (бухгалтерия) лучше использовать bcmath.
     */
    public function multiply(int|float $factor): self
    {
        return new self(
            (int) round(num: $this->amount * (float) $factor),
            $this->currency,
        );
    }

    /**
     * Равенство по значению (не по идентичности объекта).
     *   $a = Money::fromRubles(100);
     *   $b = Money::fromRubles(100);
     *   $a === $b       // false (разные объекты)
     *   $a->equals($b)  // true  (одинаковое значение)
     */
    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency(other: $other);

        return $this->amount > $other->amount;
    }

    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency(other: $other);

        return $this->amount < $other->amount;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                message: "Cannot operate on Money with different currencies: {$this->currency} vs {$other->currency}"
            );
        }
    }

    /**
     * Строковое представление для логов/dump.
     *   "1 500.00 RUB"
     */
    public function __toString(): string
    {
        return number_format(num: $this->getRubles(), decimals: 2, thousands_separator: ' ').' '.$this->currency;
    }
}
