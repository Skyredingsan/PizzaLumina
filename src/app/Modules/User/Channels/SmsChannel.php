<?php

declare(strict_types=1);

namespace App\Modules\User\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

final class SmsChannel
{
    /**
     * @param  object  $notifiable  Обычно User — у него есть свойство phone.
     * @param  Notification  $notification  Уведомление с методом toSms().
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! isset($notifiable->phone) || empty($notifiable->phone)) {
            return;
        }

        $message = $notification->toSms($notifiable);

        Log::channel('sms')->info('SMS отправлено', [
            'to' => $notifiable->phone,
            'message' => $message,
        ]);
    }
}
