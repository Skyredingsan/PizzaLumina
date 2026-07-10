<?php

declare(strict_types=1);

use App\Modules\Order\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt.auth')->group(callback: function (): void {
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders/{order}/pay', [OrderController::class, 'pay']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

    Route::middleware('role:admin')->group(callback: function (): void {
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    });
});
