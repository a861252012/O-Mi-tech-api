<?php

namespace App\Http\Middleware;

use App\Services\Auth\WebAuthService;
use App\Services\User\UserService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
        echo "Middleware".'----------';
        //$this->checkLogin();
        dd(Session::getId());
        echo session()->get(WebAuthService::SEVER_SESS_ID);
        //die;
        var_dump(\Auth::guard('pc')->user());
        if (!\Auth::guard('pc')->user()) return JsonResponse::create(['status'=>0,'未登录']);
        return $next($request);
    }

}
