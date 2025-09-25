<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserChatStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $isGuest;
    public $action; // 'new_message', 'message_read', 'chat_deleted'
    public $data;

    /**
     * Create a new event instance.
     */
    public function __construct($userId, $isGuest = false, $action = 'update', $data = [])
    {
        $this->userId = $userId;
        $this->isGuest = $isGuest;
        $this->action = $action;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Broadcast to all admin users
        return [
            new PrivateChannel('admin-chat-updates'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'userId' => $this->userId,
            'isGuest' => $this->isGuest,
            'action' => $this->action,
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
        ];
    }
}