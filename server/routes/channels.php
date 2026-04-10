<?php

use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Each user listens ONLY to their own inbox
 * Frontend: echo.private(`chat.${userId}`)
 */
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    if ((int) $user->id !== (int) $userId) {
        \Log::warning('Broadcast auth failed: chat channel', [
            'user_id' => $user->id,
            'channel_user_id' => $userId,
        ]);
        return false;
    }
    return true;
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
    if ((int) $user->id !== (int) $userId) {
        \Log::warning('Broadcast auth failed: call channel', [
            'user_id' => $user->id,
            'channel_user_id' => $userId,
        ]);
        return false;
    }
    return true;
});

Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    $normalizedGroupId = is_numeric($groupId)
        ? (int) $groupId
        : ((preg_match('/^group-(\\d+)$/', (string) $groupId, $matches) === 1) ? (int) $matches[1] : 0);
    if ($normalizedGroupId <= 0) {
        \Log::warning('Broadcast auth failed: group channel, invalid groupId', [
            'user_id' => $user->id,
            'groupId' => $groupId,
        ]);
        return false;
    }
    $isMember = GroupMember::query()
        ->where('group_id', $normalizedGroupId)
        ->where('user_id', (int) $user->id)
        ->exists();
    if (!$isMember) {
        \Log::warning('Broadcast auth failed: group channel, not a member', [
            'user_id' => $user->id,
            'groupId' => $groupId,
        ]);
    }
    return $isMember;
});

Broadcast::channel('group-call.{groupId}', function ($user, $groupId) {
    $normalizedGroupId = is_numeric($groupId)
        ? (int) $groupId
        : ((preg_match('/^group-(\\d+)$/', (string) $groupId, $matches) === 1) ? (int) $matches[1] : 0);
    if ($normalizedGroupId <= 0) {
        \Log::warning('Broadcast auth failed: group-call channel, invalid groupId', [
            'user_id' => $user->id,
            'groupId' => $groupId,
        ]);
        return false;
    }
    $isMember = GroupMember::query()
        ->where('group_id', $normalizedGroupId)
        ->where('user_id', (int) $user->id)
        ->exists();
    if (!$isMember) {
        \Log::warning('Broadcast auth failed: group-call channel, not a member', [
            'user_id' => $user->id,
            'groupId' => $groupId,
        ]);
    }
    return $isMember;
});

// If you want to change the API port from 8001 to 8000, update your .env and frontend config:
//
// In .env (backend):
// APP_URL=http://localhost:8000
// VUE_APP_API_BASE_URL=http://127.0.0.1:8000
// PUSHER_PORT=6001 (WebSocket port stays the same)
//
// In frontend .env:
// VUE_APP_API_BASE_URL=http://127.0.0.1:8000
//
// In vite.config.js or vue.config.js (frontend):
// proxy '/api' to 'http://127.0.0.1:8000'
//
// After changing, restart both backend and frontend dev servers.

