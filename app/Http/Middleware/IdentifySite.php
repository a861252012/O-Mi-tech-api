<?php

namespace App\Http\Middleware;

use Closure;

class IdentifySite
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
        $siteService = app('siteService');
        $siteDomain = $siteService->getDomainInfo();
        if (!$siteDomain->has('id')) {
            abort('404', 'Invalid Domain');
        }
        $siteInfo = $siteService->getSiteInfo();
        config('SITE_DOMAIN', $siteDomain);
        config('SITE_INFO', $siteInfo);
        return $next($request);
    }
}
