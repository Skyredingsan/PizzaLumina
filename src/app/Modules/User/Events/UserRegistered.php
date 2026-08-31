<?php

declare(strict_types=1);

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;

final readonly class UserRegistered
{
    public function __construct(public User $user)
    {
    }
}
