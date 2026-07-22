<?php

declare(strict_types=1);

use App\Modules\Product\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show'])
    ->whereNumber(parameters: 'product');

Route::middleware(['jwt.auth', 'role:admin'])
    ->group(callback: function (): void {
        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{product}', [ProductController::class, 'update'])
            ->whereNumber(parameters: 'product');
        Route::patch('products/{product}', [ProductController::class, 'update'])
            ->whereNumber(parameters: 'product');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])
            ->whereNumber(parameters: 'product');
    });
