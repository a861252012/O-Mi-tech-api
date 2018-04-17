<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;

class ThrottleRoutes extends ThrottleRequestsWithRedis
{

    public static function clear($request)
    {
        $instanse = resolve(static::class);
        $key = $instanse->resolveRequestSignature($request);
        if ($key) {
            return $instanse->redis->del($key);
        }
        return false;
    }

    protected function resolveRequestSignature($request)
    {
        $prefix = 'throttle:';
        if (!($routeName = $request->route()->getName())) {
            $routeName = $request->method() . '|' . $request->path();
        }
        return $prefix
            . $routeName . ':'
            . parent::resolveRequestSignature($request);
    }
}
