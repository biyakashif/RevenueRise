<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\GuestChatMessage;
use App\Models\GuestChat;

class NewGuestChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $guestChatMessage;
    public $guestChat;

    /**
     * Create a new event instance.
     */
    public function __construct(GuestChatMessage $guestChatMessage, GuestChat $guestChat)
    {
        $this->guestChatMessage = $guestChatMessage;
        $this->guestChat = $guestChat;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('guest-chat'), // Broadcast to admin guest chat channel
            new Channel('guest-chat.' . $this->guestChat->session_id), // Broadcast to guest
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
            'message' => [
                'id' => $this->guestChatMessage->id,
                'message' => $this->guestChatMessage->message,
                'sender_id' => $this->guestChatMessage->is_guest ? $this->guestChat->id : config('app.admin_user_id', 1),
                'recipient_id' => $this->guestChatMessage->is_guest ? config('app.admin_user_id', 1) : $this->guestChat->id,
                'created_at' => $this->guestChatMessage->created_at,
                'image_path' => null,
                'video_path' => null,
            ]
        ];
    }
}
