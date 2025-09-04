<?php
namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function updating(User $user)
    {
        if ($user->isDirty('vip_level')) {
            // VIP level is changing, reassign tasks
            $user->assignTasks();
        }
    }
}