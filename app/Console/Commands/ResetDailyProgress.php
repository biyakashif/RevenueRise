<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserOrder;
use App\Models\User;
use App\Events\UserProgressReset;

class ResetDailyProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Run this with: php artisan progress:reset-daily
     */
    protected $signature = 'progress:reset-daily';

    /**
     * The console command description.
     */
    protected $description = 'Reset all users daily progress (delete all confirmed orders for each user)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all users where tasks_auto_reset is true
        $users = User::where('role', 'user')->where('tasks_auto_reset', true)->get();

        foreach ($users as $user) {
            // ✅ Delete ALL confirmed orders for this user & their VIP level
            UserOrder::where('user_id', $user->id)
                ->where('task_name', $user->vip_level)
                ->where('status', 'confirmed')
                ->delete();

            // Reset order_reward for users with auto-reset enabled
            $user->order_reward = 0.00;
            $user->save();

            // 🔔 Fire event so frontends update in real time
            event(new UserProgressReset($user->id));
        }

        $this->info("✅ Daily progress reset successfully for users with auto-reset enabled.");
    }
}
