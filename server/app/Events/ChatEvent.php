<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $message;
    public $from_id;
    public $to_id;

    public function __construct($message, $fromId, $toId)
    {
        $this->message = $message;
        $this->from_id = $fromId;
        $this->to_id = $toId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("chat.{$this->to_id}.{$this->from_id}");
    }

    public function broadcastAs()
    {
        return 'chat.message';
    }
}
