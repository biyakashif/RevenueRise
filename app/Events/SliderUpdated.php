<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\SliderImage;

class SliderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $desktopSliders;
    public $mobileSliders;

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        // Fetch current active sliders
        $this->desktopSliders = SliderImage::desktop()->active()->ordered()->get();
        $this->mobileSliders = SliderImage::mobile()->active()->ordered()->get();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('sliders'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'slider.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'desktopSliders' => $this->desktopSliders,
            'mobileSliders' => $this->mobileSliders,
        ];
    }
}
