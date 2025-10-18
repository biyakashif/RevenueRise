<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule midnight resets
Schedule::command('progress:reset-daily')->dailyAt('00:00');
Schedule::command('profit:reset-daily')->dailyAt('00:00');
