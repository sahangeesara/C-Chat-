<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class GroupChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $group_id;
    public $from_id;
    public $message;
    public $attachment;
    public $message_id;
    public $created_at;

    public function __construct(
        int $groupId,
        int $fromId,
        string $message,
        string $attachment = '',
        ?int $messageId = null
    ) {
        $this->group_id = $groupId;
        $this->from_id = $fromId;
        $this->message = $message;
        $this->attachment = $attachment;
        $this->message_id = $messageId;
        $this->created_at = now();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('group.' . $this->group_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message_id,
            'group_id' => $this->group_id,
            'from_id' => $this->from_id,
            'body' => $this->message,
            'attachment' => $this->attachment,
            'message_id' => $this->message_id,
            'created_at' => $this->created_at,
        ];
    }
}

