<?php

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
Broadcast::routes(['middleware' => ['auth:api']]);

Broadcast::channel('chat', function ($user) {
    return ['id' => $user->id, 'name' => $user->name]; // Only authenticated users can listen
});

Broadcast::channel('chat', function ($user) {
    return Auth::check(); // Only authenticated users can listen
});
