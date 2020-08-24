<?php
/**
 * 多國語中介層
 * @author Weine
 * @date 2020-08-06
 */

namespace App\Http\Middleware;

use App\Services\UserAttrService;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VLocale
{
    const LANGS = ['zh', 'en', 'zh_TW', 'zh_HK'];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $uid = Auth::guard('mobile')->id() ?? Auth::guard()->id() ?? 0;
        $userAttrService = resolve(UserAttrService::class);
        $userLocale = $userAttrService->get($uid, 'locale');

        if (!empty($userLocale)) {
            Log::debug('用戶locale');
            App::setLocale($userLocale);
        } elseif ($queryString = $this->getQueryStringLocale()) {
            Log::debug('檢查Query String');
            App::setLocale($queryString);
        } elseif ($header = $this->getHeaderLocale()) {
            Log::debug('檢查Header');
            App::setLocale($header);
        } else {
            Log::debug('預設語系');
            App::setLocale('zh');
        }

        return $next($request);
    }

    private function getQueryStringLocale()
    {
        $locale = request()->query('locale');
        if (empty($locale)) {
            return false;
        }

        return $this->checkLocale($locale);
    }

    private function getHeaderLocale()
    {
        $locale = request()->getLanguages();
        if (count($locale) == 0) {
            return false;
        }

        return $this->checkLocale($locale[0]);
    }

    private function checkLocale($locale)
    {
        if (in_array($locale, self::LANGS)) {
            return $locale;
        }

        $localeArr = explode('_', $locale);
        if (in_array($localeArr[0], self::LANGS)) {
            return $localeArr[0];
        }

        return false;
    }
}
