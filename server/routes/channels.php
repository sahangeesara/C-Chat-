<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Private chat between two users
Broadcast::channel('chat.{toId}.{fromId}', function (User $user, $toId, $fromId) {
    // Only allow the sender or receiver to listen
    return (int) $user->id === (int) $toId || (int) $user->id === (int) $fromId;
});

// Presence channel for online users
Broadcast::channel('presence-online', function (User $user) {
    return ['id' => $user->id, 'name' => $user->name];
});

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

