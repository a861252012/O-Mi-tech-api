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
use function Psy\debug;

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

        $locale = 'zh';
        if (!empty($userLocale)) {
            $locale = $userLocale;
        } elseif (!empty($request->query('locale'))) {
            if (str_contains($request->query('locale'), '-')) {
                $localeArr = explode('-', $request->query('locale'));
            }

            if (in_array($request->query('locale'), self::LANGS)) {
                $locale = $request->query('locale');
            } elseif (in_array($localeArr[0] . '_' . $localeArr[1], self::LANGS)) {
                $locale = $localeArr[0] . '_' . $localeArr[1];
            } elseif (in_array($localeArr[0], self::LANGS)) {
                $locale = $localeArr[0];
            }
        } elseif (!empty($request->header('Accept-Language'))) {
            if (str_contains($request->header('Accept-Language'), '-')) {
                $localeArr = explode('-', $request->header('Accept-Language'));
            }

            if (in_array($request->header('Accept-Language'), self::LANGS)) {
                $locale = $request->header('Accept-Language');
            } elseif (in_array($localeArr[0] . '_' . $localeArr[1], self::LANGS)) {
                $locale = $localeArr[0] . '_' . $localeArr[1];
            } elseif (in_array($localeArr[0], self::LANGS)) {
                $locale = $localeArr[0];
            }
        }

        Log::debug('用戶request語系: ' . $locale);

        App::setLocale($locale);

        return $next($request);
    }
}
