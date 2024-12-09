<?php

namespace App\Http\Middleware;

use Closure;

class SetMaxExecutionTime
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        set_time_limit(120); // Adjust the time limit as needed

        return $next($request);
    }
}
