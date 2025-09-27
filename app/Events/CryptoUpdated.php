<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CryptoUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $cryptos;
    public $action;

    public function __construct($cryptos, $action = 'updated')
    {
        $this->cryptos = $cryptos;
        $this->action = $action;
    }

    public function broadcastOn()
    {
        return new Channel('crypto-updates');
    }

    public function broadcastAs()
    {
        return 'crypto.updated';
    }
}