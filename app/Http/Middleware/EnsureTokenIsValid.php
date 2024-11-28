<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTokenIsValid
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->input('token') !== csrf_token()) {
            return redirect('/home')->with('error', 'Invalid token.');
        }
        return $next($request);
    }
}
