<?php
/**
 * Socket 代理線路 服務
 * @author Weine
 * @date 2020-03-10
 */

namespace App\Services;


use App\Http\Resources\ChannelListResource;
use App\Repositories\SocketRepository;
use Illuminate\Support\Facades\Redis;

class SocketService
{
    protected $socketRepository;

    public function __construct(SocketRepository $socketRepository)
    {
        $this->socketRepository = $socketRepository;
    }

    public function channelList()
    {
        $list = json_decode(Redis::get('proxy_list'));
        if (empty($list)) {
            $list = ChannelListResource::collection($this->socketRepository->getAll());
            Redis::set('proxy_list', json_encode($list, JSON_UNESCAPED_UNICODE));
        }

        return $list;
    }
}