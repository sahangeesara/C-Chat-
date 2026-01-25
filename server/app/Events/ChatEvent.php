<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $fromId;
    public $toId;

    public function __construct($fromId, $toId, $message)
    {
        $this->fromId = $fromId;
        $this->toId = $toId;
        $this->message = $message;
    }



    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->toId . '.' . $this->fromId);
    }


    public function broadcastWith()
    {
        return [
            'from_id' => $this->fromId,
            'to_id' => $this->toId,
            'message' => $this->message,
        ];
    }

    public function broadcastAs()
    {
        return 'chat-message';
    }
}


