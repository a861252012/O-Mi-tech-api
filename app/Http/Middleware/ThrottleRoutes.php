<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use RuntimeException;

class ThrottleRoutes extends ThrottleRequestsWithRedis
{

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
