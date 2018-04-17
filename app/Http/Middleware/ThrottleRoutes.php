<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use RuntimeException;

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
        if ($user = $request->user()) {
            return $prefix . sha1($user->getAuthIdentifier() . '|' . $routeName);
        }

        if ($route = $request->route()) {
            return $prefix . sha1($route->getDomain() . '|' . $request->ip() . '|' . $routeName);
        }

        throw new RuntimeException('Unable to generate the request signature. Route unavailable.');
    }
}
