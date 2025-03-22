<?php

use App\Http\Controllers\api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class,'login']);
Route::post('signup', [AuthController::class,'signup']);
Route::post('sendPasswordResetLink', [ResetPasswordController::class,'sendEmail']);
Route::post('resetPassword', [ChangePasswordController::class,'process']);


Route::middleware(['auth:api'])->group(function () {

    Route::post('logout', [AuthController::class,'logout']);
    Route::post('refresh', [AuthController::class,'refresh']);
    Route::post('home', [AuthController::class,'home']);
    Route::get('me', [AuthController::class,'me']);

    Route::get('getUser/{name}', [UserController::class,'searchUser']);

    Route::get('chat/{id}', [ChatController::class,'chat']);
    Route::post('send', [ChatController::class,'send']);


    Route::get('/test-broadcast', function () {
        broadcast(new \App\Events\ChatEvent(auth()->user()));
        return 'Broadcast Sent!';
    });

});

