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
    public function __construct(public $chat)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Always broadcast to sender channel
        $channels[] = new PrivateChannel('chat.' . $this->chat->sender_id);
        
        // Always broadcast to recipient channel
        if ($this->chat->recipient_id && $this->chat->recipient_id != $this->chat->sender_id) {
            $channels[] = new PrivateChannel('chat.' . $this->chat->recipient_id);
        }
        
        // If this is a user message to admin, also broadcast to admin channel
        $admin = \App\Models\User::where('role', 'admin')->first();
        if ($admin && $this->chat->recipient_id == $admin->id && $this->chat->sender_id != $admin->id) {
            $channels[] = new PrivateChannel('chat.' . $admin->id);
        }
        
        return $channels;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        $chatData = is_object($this->chat) ? [
            'id' => $this->chat->id,
            'message' => $this->chat->message,
            'image_path' => $this->chat->image_path ?? null,
            'video_path' => $this->chat->video_path ?? null,
            'created_at' => $this->chat->created_at,
            'sender_id' => $this->chat->sender_id,
            'recipient_id' => $this->chat->recipient_id
        ] : $this->chat;
        
        return ['chat' => $chatData];
    }
}
