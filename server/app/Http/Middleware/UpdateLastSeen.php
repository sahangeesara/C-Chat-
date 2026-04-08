<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            try {
                $user->touchLastSeen();
            } catch (QueryException $e) {
                // Do not break API/auth flows (including /api/broadcasting/auth) for presence metadata.
                Log::warning('Skipping last_seen update', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $next($request);
    }
}
