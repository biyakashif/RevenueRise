<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewGuestChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageData;
    public $sessionId;

    public function __construct($messageData, $sessionId)
    {
        $this->messageData = $messageData;
        $this->sessionId = $sessionId;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('guest-chat'),
            new Channel('guest-chat.' . $this->sessionId),
        ];
        
        // Always broadcast to admin channel for all guest chat messages
        $admin = \App\Models\User::where('role', 'admin')->first();
        if ($admin) {
            $channels[] = new PrivateChannel('chat.' . $admin->id);
        }
        
        return $channels;
    }

    public function broadcastWith(): array
    {
        return ['message' => $this->messageData];
    }
}
