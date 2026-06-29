<?php

namespace App\Providers;

use App\Modules\User\Channels\SmsChannel;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notification::extend('sms', function ($container) {
            return $container->make(SmsChannel::class);
        });
    }
}
