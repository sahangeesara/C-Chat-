<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            $user->touchLastSeen();
        }

        return $next($request);
    }
}

