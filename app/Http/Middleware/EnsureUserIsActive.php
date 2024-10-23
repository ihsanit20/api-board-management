<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('sanctum');

        if ($user && !$user->is_active) {
            $user->currentAccessToken()->delete();

            return response()->json(['message' => 'Your account is inactive. Please contact support.'], 401);
        }

        return $next($request);
    }
}
