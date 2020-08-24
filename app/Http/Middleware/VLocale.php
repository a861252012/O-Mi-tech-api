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
        } elseif ($queryString = $this->checkQueryString()) {
            Log::debug('檢查Query String');
            App::setLocale($queryString);
        } elseif ($header = $this->checkHeader()) {
            Log::debug('檢查Header');
            App::setLocale($header);
        } else {
            Log::debug('預設語系');
            App::setLocale('zh');
        }

        return $next($request);
    }

    private function checkQueryString()
    {
        if (empty(request()->query('locale'))) {
            return false;
        }

        if (in_array(request()->query('locale'), self::LANGS)) {
            return request()->query('locale');
        }

        $localeArr = explode('_', request()->query('locale'));
        if (in_array($localeArr[0], self::LANGS)) {
            return $localeArr[0];
        }

        return false;
    }

    private function checkHeader()
    {
        if (empty(request()->header('Accept-Language'))) {
            return false;
        }

        if (in_array(request()->header('Accept-Language'), self::LANGS)) {
            return request()->header('Accept-Language');
        }

        if (str_contains(request()->header('Accept-Language'), '-')) {
            $localeArr = explode('-', request()->header('Accept-Language'));
            if (in_array($localeArr[0] . '_' . $localeArr[1], self::LANGS)) {
                return $localeArr[0] . '_' . $localeArr[1];
            } elseif (in_array($localeArr[0], self::LANGS)) {
                return $localeArr[0];
            }
        } else {
            $localeArr = explode('_', request()->header('Accept-Language'));
            if (in_array($localeArr[0], self::LANGS)) {
                return $localeArr[0];
            }
        }

        return false;
    }
}
