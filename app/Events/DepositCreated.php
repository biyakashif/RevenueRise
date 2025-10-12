<?php

namespace App\Events;

use App\Models\Deposit;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositCreated implements ShouldBroadcastNow
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
        return [
            new PrivateChannel('user.' . $this->userId),
            new Channel('deposits'), // Public channel for admin
        ];
    }

    /**
     * The data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'deposit' => [
                'id' => $this->deposit->id,
                'user_id' => $this->deposit->user_id,
                'amount' => $this->deposit->amount,
                'symbol' => $this->deposit->symbol,
                'status' => $this->deposit->status,
                'created_at' => $this->deposit->created_at,
                'title' => $this->deposit->title,
                'vip_level' => $this->deposit->vip_level,
                'slip_path' => $this->deposit->slip_path,
                'user_name' => $this->deposit->user->name ?? 'Unknown',
            ],
            'type' => 'deposit_created'
        ];
    }
}
