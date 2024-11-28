<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckSession
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->session->ends_at < now()) {
            Auth::logout();
            return response()->json(['message' => 'Session expired'], 401);
        }
        return $next($request);
    }
}
