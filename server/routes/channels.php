<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;


/**
 * Each user listens ONLY to their own inbox
 * Frontend: echo.private(`chat.${userId}`)
 */
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});


/**
 * Presence channel for online users
 */
Broadcast::channel('presence-online', function (User $user) {
    return [
        'id'   => $user->id,
        'name' => $user->name,
    ];
});
