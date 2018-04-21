<?php

namespace App\Http\Middleware;

use App\Services\Site\SiteService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class IdentifySite
{
    protected $siteService;

    public function __construct(SiteService $siteService)
    {
        $this->siteService = $siteService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->siteService->fromRequest($request);
        if (!$this->siteService->isValid()) {
            return JsonResponse::create([
                'status' => 0,
                'msg' => $this->siteService->errors()->first(),
            ]);
        }
        $this->siteService->shareConfigWithViews();
        /** @var Response $response */
        $response = $next($request);
        if ($this->siteService->siteTokenNeedsRefresh()) {
            $siteToken = $this->siteService->genSiteToken();
            $response->withCookie(cookie($this->siteService::SITE_TOKEN_NAME, $siteToken, $this->siteService::SITE_TOKEN_LIFETIME_MINUTES, '/', null, false, true));
            $response->header('Set-' . $this->siteService::SITE_TOKEN_NAME, $siteToken, true);
        }
        return $response;
    }
}
