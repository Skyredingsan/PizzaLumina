<?php

declare(strict_types=1);

namespace App\Modules\Order\Enums;

enum DeliveryMethod: string
{
    case Pickup = 'pickup';
    case Courier = 'courier';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(callback: static fn (self $case) => $case->value, array: self::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function rule(): array
    {
        return ['in:' . implode(separator: ',', array: self::values())];
    }

    public function requiresAddress(): bool
    {
        return match ($this) {
            self::Courier => true,
            self::Pickup => false,
        };
    }

    public function label(): string
    {
        return trans(key: "order.delivery_methods.{$this->value}");
    }
}
