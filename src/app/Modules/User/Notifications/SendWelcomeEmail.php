<?php

declare(strict_types=1);

namespace App\Modules\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SendWelcomeEmail extends Notification
{
    use Queueable;

    public function __construct(private readonly string $name)
    {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())->subject(subject: __(key: 'mail.welcome.subject'))->greeting(greeting: __(key: 'mail.welcome.greeting', replace: ['name' => $this->name]))->line(line: __(key: 'mail.welcome.line'));
    }
}
