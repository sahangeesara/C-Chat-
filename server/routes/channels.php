<?php

use App\Models\GroupMember;
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
        'id' => $user->id,
        'name' => $user->name,
        'profile_photo_url' => $user->profile_photo_url,
        'last_seen_at' => $user->last_seen_at?->toIso8601String(),
        'online' => true,
    ];
});
Broadcast::channel('call.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    $normalizedGroupId = is_numeric($groupId)
        ? (int) $groupId
        : ((preg_match('/^group-(\d+)$/', (string) $groupId, $matches) === 1) ? (int) $matches[1] : 0);

    if ($normalizedGroupId <= 0) {
        return false;
    }

    return GroupMember::query()
        ->where('group_id', $normalizedGroupId)
        ->where('user_id', (int) $user->id)
        ->exists();
});

Broadcast::channel('group-call.{groupId}', function ($user, $groupId) {
    $normalizedGroupId = is_numeric($groupId)
        ? (int) $groupId
        : ((preg_match('/^group-(\d+)$/', (string) $groupId, $matches) === 1) ? (int) $matches[1] : 0);

    if ($normalizedGroupId <= 0) {
        return false;
    }

    return GroupMember::query()
        ->where('group_id', $normalizedGroupId)
        ->where('user_id', (int) $user->id)
        ->exists();
});
