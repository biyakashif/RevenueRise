<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VipLevelUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $vip_level;
    public $mobile_number;

    public function __construct($mobile_number, $vip_level)
    {
        $this->mobile_number = $mobile_number;
        $this->vip_level = $vip_level;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->mobile_number);
    }

    public function broadcastWith()
    {
        return ['vip_level' => $this->vip_level];
    }
}