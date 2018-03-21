<?php

namespace App\Http\Middleware;

use Closure;

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
        if(!\Auth::check())  return $next($request);

        $user = \Auth::getUser();
        $origin = $user['origin'];
        if ($origin >= 50) {
            $rs = app('roomService');
            header('Location:' . $rs->getPlatUrl($origin)['pay']);
        }
        return $next($request);
    }
}
