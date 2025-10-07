<?php

namespace App\Events;

use App\Models\Deposit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deposit;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(Deposit $deposit)
    {
        $this->deposit = $deposit;
        $this->userId = $deposit->user->id;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->userId);
    }

    /**
     * The data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'deposit' => [
                'id' => $this->deposit->id,
                'amount' => $this->deposit->amount,
                'symbol' => $this->deposit->symbol,
                'status' => $this->deposit->status,
                'created_at' => $this->deposit->created_at,
                'title' => $this->deposit->title,
                'vip_level' => $this->deposit->vip_level,
            ],
            'type' => 'deposit_created'
        ];
    }
}
