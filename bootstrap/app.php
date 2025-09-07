<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IsAdmin;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'admin' => IsAdmin::class,
            'is_admin' => IsAdmin::class,
        ]);
    })
    ->withProviders([
        \App\Providers\BroadcastServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withCommands([
        \App\Console\Commands\ResetUserTasks::class,
        \App\Console\Commands\ResetDailyProgress::class,
        \App\Console\Commands\ResetTodaysProfit::class, // Register the new command
    ])
    ->withSchedule(function (Schedule $schedule) {
        // Auto reset progress every midnight
        $schedule->command('progress:reset-daily')->dailyAt('00:00');
        // Auto reset todays_profit every midnight
        $schedule->command('profit:reset-daily')->dailyAt('00:00');
        $schedule->command('tasks:reset')->dailyAt('00:00');
    })
    ->create();