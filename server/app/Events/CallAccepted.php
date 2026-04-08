<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $fromId,
        public int $toId,
        public int $callId,
        public string $callType = 'audio'
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('call.' . $this->toId);
    }

    public function broadcastAs(): string
    {
        return 'call.accepted';
    }

    public function broadcastWith(): array
    {
        return [
            'from_id' => $this->fromId,
            'to_id' => $this->toId,
            'call_id' => $this->callId,
            'call_type' => $this->callType,
            'fromId' => $this->fromId,
            'toId' => $this->toId,
            'callId' => $this->callId,
            'callType' => $this->callType,
        ];
    }
}

