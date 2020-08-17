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
            $locale = $userLocale;
        } elseif (!empty($request->query('locale'))) {
            $localeArr = explode('_', $request->query('locale'));

            if (in_array($request->query('locale'), self::LANGS)) {
                $locale = $request->query('locale');
            } elseif (in_array($localeArr[0], self::LANGS)) {
                $locale = $localeArr[0];
            }
        } else {
            $locale = 'zh';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
