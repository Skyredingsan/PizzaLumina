<?php

declare(strict_types=1);

namespace App\Modules\User\Listeners;

use App\Modules\User\Events\UserRegistered;
use App\Modules\User\Notifications\SendWelcomeEmail;

final class SendWelcomeEmailListener
{
    public function handle(UserRegistered $event): void
    {
        $event->user->notify(instance: new SendWelcomeEmail(name: $event->user->name));
    }
}
