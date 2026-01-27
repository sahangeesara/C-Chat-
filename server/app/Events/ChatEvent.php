<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatEvent implements ShouldBroadcast
{
    public $from_id;
    public $to_id;
    public $message;
    public $created_at;

    public function __construct($from, $to, $message)
    {
        $this->from_id = $from;
        $this->to_id = $to;
        $this->message = $message;
        $this->created_at = now();
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->to_id);
    }

    public function broadcastAs()
    {
        return 'chat.message';
    }
}

