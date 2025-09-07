<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public ChatMessage $chat)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to both sender and recipient channels
        return [
            new PrivateChannel('chat.' . $this->chat->sender_id),
            new PrivateChannel('chat.' . $this->chat->recipient_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'chat' => [
                'id' => $this->chat->id,
                'message' => $this->chat->message,
                'image_path' => $this->chat->image_path,
                'created_at' => $this->chat->created_at,
                'sender_id' => $this->chat->sender_id,
                'recipient_id' => $this->chat->recipient_id
            ],
        ];
    }
}
