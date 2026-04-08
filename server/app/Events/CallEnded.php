<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $fromId,
        public int $toId,
        public int $callId,
        public string $status = 'ended',
        public ?string $reason = null
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('call.' . $this->toId);
    }

    public function broadcastAs(): string
    {
        return 'call.ended';
    }

    public function broadcastWith(): array
    {
        return [
            'from_id' => $this->fromId,
            'to_id' => $this->toId,
            'call_id' => $this->callId,
            'status' => $this->status,
            'reason' => $this->reason,
            'fromId' => $this->fromId,
            'toId' => $this->toId,
            'callId' => $this->callId,
        ];
    }
}

