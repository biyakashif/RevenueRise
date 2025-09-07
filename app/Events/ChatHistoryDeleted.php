<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatHistoryDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userMobile;
    public $adminMobile;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($userMobile, $adminMobile)
    {
        $this->userMobile = $userMobile;
        $this->adminMobile = $adminMobile;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->userMobile);
    }

    public function broadcastWith()
    {
        return [
            'userMobile' => $this->userMobile,
            'adminMobile' => $this->adminMobile,
        ];
    }
}
