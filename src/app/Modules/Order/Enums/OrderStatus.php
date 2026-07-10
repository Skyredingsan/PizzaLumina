<?php

declare(strict_types=1);

namespace App\Modules\Order\Enums;

enum OrderStatus: string
{
    case Created = 'created';
    case Paid = 'paid';
    case InProgress = 'in_progress';
    case Delivering = 'delivering';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(array: self::cases(), column_key: 'value');
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Created => in_array(needle: $next, haystack: [self::Paid, self::Cancelled], strict: true),
            self::Paid => in_array(needle: $next, haystack: [self::InProgress, self::Cancelled], strict: true),
            self::InProgress => in_array(needle: $next, haystack: [self::Delivering, self::Cancelled], strict: true),
            self::Delivering => $next === self::Completed,
            self::Completed, self::Cancelled => false,
        };
    }
}
