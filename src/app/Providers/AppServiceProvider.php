<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\User\Channels\SmsChannel;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsChannel::class);
    }

    /**
     * Bootstrap сервисов приложения.
     */
    public function boot(): void
    {
        Notification::extend('sms', static function (Container $app): SmsChannel {
            return $app->make(SmsChannel::class);
        });
    }
}
