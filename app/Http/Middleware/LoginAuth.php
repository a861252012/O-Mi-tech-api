<?php

namespace App\Http\Middleware;

use App\Services\UserService;
use Closure;

class LoginAuth
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
        echo "Middleware";
        //$this->checkLogin();
        var_dump(app()->make(UserService::class)->checkLogin());
        return $next($request);
    }

}
