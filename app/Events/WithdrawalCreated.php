<?php

namespace App\Events;

use App\Models\Withdraw;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $withdrawal;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(Withdraw $withdrawal)
    {
        $this->withdrawal = $withdrawal;
        $this->userId = $withdrawal->user_id;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel('user.' . $this->userId),
            new Channel('withdrawals'), // Public channel for admin
        ];
    }

    /**
     * The data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'withdrawal' => [
                'id' => $this->withdrawal->id,
                'user_id' => $this->withdrawal->user_id,
                'amount_withdraw' => $this->withdrawal->amount_withdraw,
                'crypto_wallet' => $this->withdrawal->crypto_wallet,
                'crypto_symbol' => $this->withdrawal->crypto_symbol,
                'crypto_amount' => $this->withdrawal->crypto_amount,
                'status' => $this->withdrawal->status,
                'created_at' => $this->withdrawal->created_at,
                'user_name' => $this->withdrawal->user->name ?? 'Unknown',
            ],
            'type' => 'withdrawal_created'
        ];
    }
}
