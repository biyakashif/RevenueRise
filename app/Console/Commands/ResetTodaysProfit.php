<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class ResetTodaysProfit extends Command
{
    protected $signature = 'profit:reset-daily';
    protected $description = 'Reset todays_profit and last_profit_reset for all users daily';

    public function handle()
    {
        User::query()->update([
            'todays_profit' => 0.00,
            'last_profit_reset' => Carbon::today(),
        ]);

        $this->info('Todays profit and last profit reset updated successfully for all users.');
    }
}