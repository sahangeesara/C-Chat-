<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncomingCall implements ShouldBroadcastNow
{
    public $fromId;
    public $toId;
    public $offer;
    public $callId;

    public function __construct($fromId, $toId, $offer, $callId = null)
    {
        $this->fromId = $fromId;
        $this->toId   = $toId;
        $this->offer  = $offer;
        $this->callId = $callId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('call.' . $this->toId);
    }

    public function broadcastAs()
    {
        return 'incoming.call';
    }
}
