<?php

declare(strict_types=1);

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class UserRegistered implements ShouldDispatchAfterCommit
{
    public function __construct(public User $user)
    {
    }
}
