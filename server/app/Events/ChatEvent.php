<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct($message, User $user)
    {
        $this->message = $message;
        $this->user = [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    /**
     * Data sent when broadcasting.
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'user' => $this->user,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat')]; // Ensure this matches your frontend listener
    }
}
