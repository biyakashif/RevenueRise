<?php
namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function updating(User $user)
    {
        if ($user->isDirty('vip_level')) {
            // VIP level is changing - tasks should be manually assigned via TaskManager
            \Log::info('VIP level changed for user: ' . $user->id . ' to ' . $user->vip_level);
        }
    }
}