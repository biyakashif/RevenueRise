<?php

// app/Events/OrderConfirmed.php
namespace App\Events;

use App\Models\UserOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OrderConfirmed implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $order;
    public $userId;

    public function __construct(UserOrder $order)
    {
        $this->order = $order->load('product');
        $this->userId = $order->user_id;
    }

    public function broadcastOn()
    {
        // private channel for that user
        return new PrivateChannel('orders.' . $this->userId);
         return [
        new PrivateChannel('orders.' . $this->userId),   // per-user
        new Channel('admin.orders')                     // admin-wide
    ];
    }

    public function broadcastAs()
    {
        return 'OrderConfirmed';
    }


}
