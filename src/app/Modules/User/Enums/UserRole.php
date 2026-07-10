<?php

declare(strict_types=1);

namespace App\Modules\User\Enums;

enum UserRole: string
{
    case Guest = 'guest';
    case Customer = 'customer';
    case Admin = 'admin';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function weight(): int
    {
        return match ($this) {
            self::Guest => 0,
            self::Customer => 1,
            self::Admin => 2,
        };
    }
}
