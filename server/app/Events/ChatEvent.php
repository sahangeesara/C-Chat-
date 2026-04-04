<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class ChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $from_id;
    public $to_id;
    public $message;
    public $attachment;
    public $message_id;
    public $created_at;

    public function __construct($from, $to, $message, $attachment = '', $messageId = null)
    {
        $this->from_id = $from;
        $this->to_id = $to;
        $this->message = $message;
        $this->attachment = $attachment;
        $this->message_id = $messageId;
        $this->created_at = now();
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('chat.' . $this->from_id),
            new PrivateChannel('chat.' . $this->to_id),
        ];
    }

    public function broadcastAs()
    {
        return 'chat.message';
    }

    public function broadcastWith(): array
    {
        return [
            'from_id' => $this->from_id,
            'to_id' => $this->to_id,
            'message' => $this->message,
            'attachment' => $this->attachment,
            'message_id' => $this->message_id,
            'created_at' => $this->created_at,
        ];
    }
}

