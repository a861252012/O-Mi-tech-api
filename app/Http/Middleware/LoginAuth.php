<?php

namespace App\Http\Middleware;

use App\Services\Auth\JWTAuthService;
use App\Services\Auth\WebAuthService;
use App\Services\User\UserService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
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
        $client = WebAuthService::guard;;
        if(strpos($request->getPathInfo(),'/m/')===0){
            $client = JWTAuthService::guard;
        }
        $request->offsetSet('guard',$client);
        if (!\Auth::guard($client)->user()) return JsonResponse::create(['status'=>0,'未登录']);
        return $next($request);
    }

}
