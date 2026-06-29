<?php

declare(strict_types=1);

namespace App\Modules\User\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

final class SmsChannel
{
    /**
     * @param  mixed  $notifiable  Обычно User — у кого есть phone
     * @param  SendWelcomeSms  $notification  Само уведомление
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! $notifiable->phone) {
            return;
        }

        $message = $notification->toSms($notifiable);

        Log::channel('sms')->info('SMS отправлено', [
            'to'      => $notifiable->phone,
            'message' => $message,
        ]);
    }
}
