<?php

namespace App\Http;

use App\Http\Middleware\Charge;
use App\Http\Middleware\FormatResponse;
use App\Http\Middleware\LoginAuth;
use App\Http\Middleware\MobileSession;
use App\Http\Middleware\Test;
use App\Http\Middleware\ThrottleRoutes;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Middleware\VLocale;
use App\Http\Middleware\V2Auth;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        Charge::class,
        FormatResponse::class,
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\IdentifySite::class,
        Test::class,
        VLocale::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'pc' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            VLocale::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,//根据用户id或ip进行全局限制，不同路由使用同一个限制key
        'throttle.route' => ThrottleRoutes::class,//自定义限制，基于redis，前缀throttle:，不同路由分开限制
        'login_auth' => LoginAuth::class,
        'charge' => Charge::class,
        'test' => Test::class,
        'mobile.session' => MobileSession::class,
        'V2Auth' => V2Auth::class,
    ];
}
