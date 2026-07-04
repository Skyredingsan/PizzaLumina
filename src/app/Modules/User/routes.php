<?php

declare(strict_types=1);

use App\Modules\User\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/**
 * Роуты аутентификации.
 *
 * /api/auth/register, /api/auth/login — публичные.
 * /api/auth/logout, /api/auth/me, /api/auth/refresh — требуют валидный JWT.
 */
Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('jwt.auth')->group(function (): void {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::get('me',       [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});
