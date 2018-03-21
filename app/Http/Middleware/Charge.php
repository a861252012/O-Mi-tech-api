<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Charge
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
        if(Auth::guest())  return $next($request);

        $user = Auth::getUser();
        $origin = $user['origin']??0;
        if ($origin >= 50) {
            $rs = app('roomService');
            header('Location:' . $rs->getPlatUrl($origin)['pay']);
        }
        return $next($request);
    }
}
