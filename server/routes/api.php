<?php

use App\Http\Controllers\api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('login', [AuthController::class,'login']);
Route::post('signup', [AuthController::class,'signup']);
Route::post('sendPasswordResetLink', [ResetPasswordController::class,'sendEmail']);
Route::post('resetPassword', [ChangePasswordController::class,'process']);

Route::middleware(['auth:api'])->group(function () {

    Route::post('logout', [AuthController::class,'logout']);
    Route::post('refresh', [AuthController::class,'refresh']);
    Route::get('me', [AuthController::class,'me']);

    // User routes
    Route::get('searchUser/{name}', [UserController::class, 'searchUser']); // search by name
    Route::get('getUser/{name}', [UserController::class, 'searchUser']);
    Route::get('getUser/{id}', [UserController::class, 'getUser']);
    // get by ID
    Route::apiResource('user', UserController::class);

    // Chat routes
    Route::get('chat/{id}', [ChatController::class,'chat']);
    Route::post('send', [ChatController::class,'send']);

    // Test broadcast
    Route::get('/test-broadcast', function () {
        broadcast(new \App\Events\ChatEvent(auth()->user(), "TEST MESSAGE"));
        return 'sent';
    });

    // Broadcasting auth
    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });
});

Route::middleware('auth:api')->post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});
Route::middleware('auth:api')->get('/debug-auth', function(Request $request){
    return $request->user();
});


// Broadcasting with Sanctum
Broadcast::routes(['middleware' => ['auth:sanctum']]);
