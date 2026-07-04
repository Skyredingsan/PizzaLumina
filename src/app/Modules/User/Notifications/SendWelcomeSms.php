<?php

declare(strict_types=1);

namespace App\Modules\User\Notifications;

use Illuminate\Notifications\Notification;

class SendWelcomeSms extends Notification
{
    public function __construct(
        private readonly string $name,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['sms'];
    }


    public function toSms(object $notifiable): string
    {
        return "Добро пожаловать в PizzaLumina, {$this->name}! Ваш аккаунт создан.";
    }
}
