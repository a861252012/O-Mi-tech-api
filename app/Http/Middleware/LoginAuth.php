<?php

namespace App\Http\Middleware;

use App\Services\Auth\JWTGuard;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle ($request, Closure $next,$guard=null)
    {
//        $client = SessionGuard::guard;;
//        if (Str::startsWith($request->getPathInfo(), '/m/')) {
//            $client = JWTGuard::guard;
//        }
//        $request->offsetSet('guard', $client);
//        dd($request->all());
        if ($guard) {
            config()->set('auth.defaults.guard', $guard);
        }
        if (Auth::guest()) {
            return JsonResponse::create(['status' => 10000, 'msg' => '未登录']);
        }
        if (Auth::user()->banned()) {
            Auth::logout();
            throw new HttpResponseException(JsonResponse::create(['status' => 0, 'msg' => 'banned']));
        }
        return $next($request);
    }
}
