<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/
Broadcast::routes(['middleware' => ['auth:sanctum']]); // 👈 Use 'auth:sanctum' for API authentication

Broadcast::channel('chat.{userId}', function (User $user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('chat', function ($user) {
    return Auth::check(); // Only authenticated users can listen
});


Broadcast::channel('presence-online', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
