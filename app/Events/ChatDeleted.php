<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $isGuest;

    public function __construct($userId, $isGuest = false)
    {
        $this->userId = $userId;
        $this->isGuest = $isGuest;
    }

    public function broadcastOn(): array
    {
        $admin = \App\Models\User::where('role', 'admin')->first();
        
        return [
            new PrivateChannel('chat.' . $admin->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'is_guest' => $this->isGuest,
        ];
    }
}