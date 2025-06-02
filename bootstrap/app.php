<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // Send meeting reminders 15 minutes before each meeting
        $schedule->command('meetings:send-reminders --minutes=15')
            ->everyMinute();

        // Send meeting reminders 1 day before each meeting
        $schedule->command('meetings:send-reminders --minutes=1440')
            ->dailyAt('09:00');
    })
    ->create();
