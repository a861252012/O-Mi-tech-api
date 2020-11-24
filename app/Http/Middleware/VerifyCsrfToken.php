<?php

namespace App\Http\Middleware;

use Closure;
use App\Facades\SiteSer;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // 內部調用
        'api/omey/*',

        // 後台調用
        'api/charge/checkKeepVip',

        // 充值平台回調
        'api/charge/notice*',

        // 合作平台後端調用
        'api/v1/deposit',
    ];

    // 這是一個暫時的處理方式，可由後台先開關。
    // 等全部 CSRF 請求問題排除後，可直接移除這個繼承的 handler。
    public function handle($request, Closure $next)
    {
        if ($this->isReading($request) ||
            $this->runningUnitTests() ||
            $this->inExceptArray($request) ||
            $this->tokensMatch($request)
        ) {
            return $this->addCookieToResponse($request, $next($request));
        }

        $check_csrf = SiteSer::globalSiteConfig('enable_csrf');
        if ($check_csrf) {
            Log::channel('csrf')->error($request->path());
            throw new TokenMismatchException;
        }

        // log and return next
        Log::channel('csrf')->warn($request->path());
        return $this->addCookieToResponse($request, $next($request));
    }
}
