<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Cart\Services\CartService;
use App\Modules\Order\Services\OrderService;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Observers\ProductObserver;
use App\Modules\User\Channels\SmsChannel;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsChannel::class);
        $this->app->singleton(CartService::class);
        $this->app->singleton(OrderService::class);
    }

    public function boot(): void
    {
        Notification::extend('sms', static fn (Container $app): SmsChannel => $app->make(SmsChannel::class));

        Product::observe(classes: ProductObserver::class);
    }
}
