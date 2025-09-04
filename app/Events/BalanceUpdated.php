<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class BalanceUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $mobile_number;
    public $balance;

    public function __construct(User $user)
    {
        $this->mobile_number = $user->mobile_number;
        $this->balance = $user->balance;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->mobile_number);
    }

    public function broadcastWith()
    {
        return ['balance' => $this->balance];
    }
}
