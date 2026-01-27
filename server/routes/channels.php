<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:api']]);

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Private chat between two users
Broadcast::channel('chat.{toId}.{fromId}', function ($user, $toId, $fromId) {
    return (int) $user->id === (int) $toId
        || (int) $user->id === (int) $fromId;
});

// Presence channel for online users
Broadcast::channel('presence-online', function (User $user) {
    return [
        'id'   => $user->id,
        'name' => $user->name
    ];
});

Broadcast::channel('chat', function ($user) {
    return true; // allow authenticated user
});
