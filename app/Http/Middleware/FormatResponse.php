<?php

namespace App\Http\Middleware;

use App\Services\UserAttrService;
use Closure;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FormatResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     * @throws \Error
     */
    public function handle($request, Closure $next)
    {
        /* 設定多國語系 */
//        $userAttrService = resolve(UserAttrService::class);
//        $userLocale = $userAttrService->get(Auth::id(), 'locale');
//        if (!empty($userLocale)) {
//            $locale = $userLocale;
//        } elseif (!empty($request->query('locale'))) {
//            $locale = $request->query('locale');
//        } else {
//            $locale = 'zh';
//        }
//
//        App::setLocale($locale);

        $response = $next($request);

        if ($response instanceof Response) {
            if ($response instanceof JsonResponse) {

                $json = $response->getData();
                if (!is_object($json)) {
                    throw new \Error('返回格式错误');
                }
                if (!isset($json->data)) {
                    $json->data = new \stdClass();
                }
                if (!isset($json->msg)) {
                    $json->msg = '';
                }
                if (!isset($json->status)) {
                    $json->status = 1;
                }
                return $response->setData($json);
            }
        }

        return $response;
    }
}
