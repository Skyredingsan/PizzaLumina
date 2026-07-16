<?php

declare(strict_types=1);

use App\Modules\Cart\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt.auth')->group(callback: function (): void {
    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'add']);
    Route::patch('cart/items/{item}', [CartController::class, 'update']);
    Route::delete('cart/items/{item}', [CartController::class, 'remove']);
    Route::delete('cart', [CartController::class, 'clear']);
});
