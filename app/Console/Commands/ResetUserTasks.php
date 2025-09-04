<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ResetUserTasks extends Command
{
    protected $signature = 'tasks:reset';
    protected $description = 'Reset and reassign tasks for all users at midnight';

    public function handle()
    {
        User::where('role', 'user')->get()->each(function ($user) {
            $user->assignTasks();
        });
        $this->info('All user tasks have been reset and reassigned.');
    }
}