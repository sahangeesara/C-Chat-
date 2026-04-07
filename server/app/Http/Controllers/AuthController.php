<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignUpRequest;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','signup']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Email or password does not exist'], 401);
        }

        Auth::user()?->touchLastSeen();

        return $this->respondWithToken($token);
    }
    public function signup(SignUpRequest $request)
    {
        User::create($request->validated());

        return $this->login();
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        Auth::user()?->touchLastSeen();

        return response()->json([
            'user' => Auth::user(),
            'token' => request()->bearerToken() // ✅ Check if token is received
        ]);
    }
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        if ($user = Auth::user()) {
            // Mark offline immediately instead of waiting for the is_online grace window.
            $user->forceFill([
                'last_seen_at' => now()->subMinutes(3),
            ])->saveQuietly();
        }
        Auth::logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => Auth::user(),
            'user_name' => Auth::user()?->name,
        ]);
    }
}
