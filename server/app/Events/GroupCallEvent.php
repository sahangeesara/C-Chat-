<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupCallEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $groupId,
        public string $action,
        public array $payload = []
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('group-call.' . $this->groupId);
    }

    public function broadcastAs(): string
    {
        return 'group.call';
    }

    public function broadcastWith(): array
    {
        return array_merge([
            'group_id' => $this->groupId,
            'action' => $this->action,
        ], $this->payload);
    }
}

