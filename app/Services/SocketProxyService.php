<?php
/**
 * Socket 代理線路 服務
 * @author Weine
 * @date 2020-03-10
 */

namespace App\Services;


use App\Http\Resources\ProxyListResource;
use App\Repositories\SocketProxyRepository;
use Illuminate\Support\Facades\Redis;

class SocketProxyService
{
    protected $socketProxyRepository;

    public function __construct(SocketProxyRepository $socketProxyRepository)
    {
        $this->socketProxyRepository = $socketProxyRepository;
    }

    public function proxyList()
    {
        $list = json_decode(Redis::get('proxy_list'));

        if (empty($list)) {
            $list = ProxyListResource::collection($this->socketProxyRepository->getAll());
            Redis::set('proxy_list', json_encode($list, JSON_UNESCAPED_UNICODE));
        }

        return $list;
    }
}