<?php

namespace App\Http\Middleware;

use App\Services\Auth\JWTAuthService;
use App\Services\Auth\WebAuthService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
//        $client = SessionGuard::guard;;
//        if (Str::startsWith($request->getPathInfo(), '/m/')) {
//            $client = JWTGuard::guard;
//        }
//        $request->offsetSet('guard', $client);
//        dd($request->all());
        if (Auth::guest()) return JsonResponse::create(['status' => 0, 'msg'=>'未登录']);
        return $next($request);
    }

}
