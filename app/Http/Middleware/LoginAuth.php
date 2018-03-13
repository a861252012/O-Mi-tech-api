<?php

namespace App\Http\Middleware;

use App\Services\User\UserService;
use Closure;
use Illuminate\Http\JsonResponse;

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
        var_dump(\Auth::guard('pc')->user());
        if (!\Auth::guard('pc')->user()) return JsonResponse::create(['status'=>0,'未登录']);
        return $next($request);
    }

}
