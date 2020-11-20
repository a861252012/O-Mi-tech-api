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
        $origin = (int)$request->origin;

        try {
            if (in_array($origin, [22, 32])) {
                $uid = Auth::guard('mobile')->id();
            } else {
                $uid = Auth::guard()->id();
            }
            if ($uid) {
                $userAttrService = resolve(UserAttrService::class);
                $userLocale = $userAttrService->get($uid, 'locale');
            }
        } catch (\Exception $e) {
            $userLocale = null;
        }

//        $uid = Auth::guard('mobile')->id() ?? Auth::guard()->id() ?? 0;
//        $userAttrService = resolve(UserAttrService::class);
//        $userLocale = $userAttrService->get($uid, 'locale');

        if (!empty($userLocale)) {
            App::setLocale($userLocale);
        } elseif ($queryString = $this->getQueryStringLocale()) {
            App::setLocale($queryString);
        } elseif ($header = $this->getHeaderLocale()) {
            App::setLocale($header);
        } else {
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
