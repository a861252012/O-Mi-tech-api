<?php

namespace App\Http\Middleware;

use Closure;

class CheckInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $request->replace(array_map('addslashes', $request->all()));
        return $next($request);
    }
}
