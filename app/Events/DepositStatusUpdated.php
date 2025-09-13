<?php

namespace App\Events;

use App\Models\Deposit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositStatusUpdated implements ShouldBroadcast
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
                'status' => $this->deposit->status,
                'amount' => $this->deposit->amount,
                'symbol' => $this->deposit->symbol,
                'updated_at' => $this->deposit->updated_at,
            ],
            'type' => 'deposit_status_updated'
        ];
    }
}
