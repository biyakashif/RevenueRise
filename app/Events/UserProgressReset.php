<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserProgressReset implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('orders.' . $this->userId),  // notify user
            new Channel('admin.orders')                     // notify admin dashboard
        ];
    }

    public function broadcastAs()
    {
        return 'UserProgressReset';
    }
}
