<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('reports:schedule-daily')
    ->dailyAt('02:00')
    ->name('reports-daily')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('reports:publish-outbox')->everyMinute()->withoutOverlapping();
