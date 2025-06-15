<?php

use App\Http\Middleware\EnsureWorkspaceIsSelected;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([
            EnsureWorkspaceIsSelected::class,
        ]);
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

        $schedule->command('wiki:generate all')
            ->dailyAt('00:00')
            ->appendOutputTo(storage_path('logs/wiki-generator.log'));

        // Health Monitoring Tasks
        // Update project health metrics daily at 6 AM
        $schedule->command('projects:update-health')
            ->dailyAt('06:00')
            ->appendOutputTo(storage_path('logs/health-updates.log'));

        // Send daily health digests at 9 AM
        $schedule->command('health:send-notifications --type=daily')
            ->dailyAt('09:00')
            ->appendOutputTo(storage_path('logs/health-notifications.log'));

        // Send weekly health reports on Monday at 10 AM
        $schedule->command('health:send-notifications --type=weekly')
            ->weeklyOn(1, '10:00')
            ->appendOutputTo(storage_path('logs/health-reports.log'));

        // Check for critical alerts every 15 minutes
        $schedule->command('health:send-notifications --type=critical')
            ->everyFifteenMinutes()
            ->appendOutputTo(storage_path('logs/critical-alerts.log'));
    })
    ->create();
