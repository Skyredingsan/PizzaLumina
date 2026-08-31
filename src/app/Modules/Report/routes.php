<?php

declare(strict_types=1);

use App\Modules\Report\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')
    ->middleware(['jwt.auth', 'role:admin'])
    ->group(callback: function (): void {
        Route::get('/', [ReportController::class, 'index'])->name(name: 'reports.index');
        Route::post('/', [ReportController::class, 'store'])->name(name: 'reports.store');
        Route::get('/{id}', [ReportController::class, 'show'])
            ->where(name: 'id', expression: '[0-9a-z]{26}')
            ->name(name: 'reports.show');
        Route::get('/{id}/download', [ReportController::class, 'download'])
            ->where(name: 'id', expression: '[0-9a-z]{26}')
            ->name(name: 'reports.download');
    });
