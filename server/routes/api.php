<?php

use App\Events\ChatEvent;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::post('login', [AuthController::class,'login']);
Route::post('signup', [AuthController::class,'signup']);
Route::post('sendPasswordResetLink', [ResetPasswordController::class,'sendEmail']);
Route::post('resetPassword', [ChangePasswordController::class,'process']);

Route::get('location', [LocationController::class, 'reverseGeocode']);
Route::get('location/reverse', [LocationController::class, 'reverseGeocode']);
Route::get('weather', [LocationController::class, 'weather']);

Route::middleware(['auth:api', 'last.seen'])->group(function () {

    Route::post('logout', [AuthController::class,'logout']);
    Route::post('refresh', [AuthController::class,'refresh']);
    Route::get('me', [AuthController::class,'me']);

    // User routes
    Route::get('/getUser', function () {
        return auth()->user();
    })->middleware('auth:api');

    Route::get('searchUser/{name?}', [UserController::class, 'searchUser']); // search by name
    Route::get('getUser/{id}', [UserController::class, 'show'])->whereNumber('id');
    Route::get('user-status/{id}', [UserController::class, 'status'])->whereNumber('id');
    Route::post('user/update-profile', [UserController::class, 'updateProfile']);
    // Backward compatibility: frontend search still calls /api/getUser/{name}
    Route::get('getUser/{name}', [UserController::class, 'searchUser'])
        ->where('name', '^(?!\\d+$).+');
    // Backward compatibility: some clients still request group details via /api/user/group-{id}
    Route::get('user/group-{group}', [GroupController::class, 'show'])->whereNumber('group');

    // get by ID (numeric only so group-* IDs do not hit UserController@show)
    Route::apiResource('user', UserController::class)->whereNumber('user');

    // Chat routes
    Route::get('chat/{id}', [ChatController::class,'chat']);
    Route::post('send', [ChatController::class,'send']);

//    // Test broadcast
    Route::get('/test-broadcast', function () {
        $userId = auth()->id();
        broadcast(new ChatEvent($userId, $userId, 'HELLO REALTIME', '', null));

        return response()->json([
            'status' => 'sent',
            'channel' => 'chat.' . $userId,
            'event' => 'chat.message',
        ]);
    });

    // routes/api.php
    Route::post('/call/start', [CallController::class, 'start']);
    Route::post('/call/answer', [CallController::class, 'answer']);
    Route::post('/call/accept', [CallController::class, 'answer']);
    Route::post('/call/ice', [CallController::class, 'ice']);
    Route::post('/call/end', [CallController::class, 'end']);
    Route::get('/call/history', [CallController::class, 'history']);
    Route::get('/call/{id}', [CallController::class, 'show'])->whereNumber('id');


    // Group routes
    Route::get('groups', [GroupController::class, 'index']);
    Route::post('groups', [GroupController::class, 'store']);
    Route::get('groups/{group}', [GroupController::class, 'show']);
    Route::patch('groups/{group}', [GroupController::class, 'update']);
    Route::post('groups/{group}/members', [GroupController::class, 'addMembers']);
    Route::patch('groups/{group}/members/{user}/role', [GroupController::class, 'updateMemberRole']);
    Route::delete('groups/{group}/members/{user}', [GroupController::class, 'removeMember']);
    Route::post('groups/{group}/leave', [GroupController::class, 'leave']);
    Route::delete('groups/{group}', [GroupController::class, 'destroy']);

});
