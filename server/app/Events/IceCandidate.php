<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IceCandidate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public $fromId,
                                public $toId,
                                 public $candidate,
                                 public $callId = null)
    {

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('call.' . $this->toId);
    }

    public function broadcastAs(): string
    {
        return 'ice.candidate';
    }

    public function broadcastWith(): array
    {
        return [
            'from_id' => $this->fromId,
            'to_id' => $this->toId,
            'fromId' => $this->fromId,
            'toId' => $this->toId,
            'candidate' => $this->candidate,
            'call_id' => $this->callId,
            'callId' => $this->callId,
        ];
    }
}
