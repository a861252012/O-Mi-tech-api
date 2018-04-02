<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
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
