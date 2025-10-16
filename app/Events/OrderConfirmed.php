<?php

// app/Events/OrderConfirmed.php
namespace App\Events;

use App\Models\UserOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class OrderConfirmed implements ShouldBroadcastNow
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
        return [
            new PrivateChannel('orders.' . $this->userId),
            new Channel('admin.orders')
        ];
    }

    public function broadcastAs()
    {
        return 'OrderConfirmed';
    }

    public function broadcastWith()
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'user_id' => $this->order->user_id,
                'product_id' => $this->order->product_id,
                'status' => $this->order->status,
            ]
        ];
    }
}
