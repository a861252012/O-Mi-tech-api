<?php
/**
 * Socket 代理線路 服務
 * @author Weine
 * @date 2020-03-10
 */

namespace App\Services;


use App\Http\Resources\ChannelListResource;
use App\Repositories\SocketRepository;

class SocketService
{
    protected $socketRepository;

    public function __construct(SocketRepository $socketRepository)
    {
        $this->socketRepository = $socketRepository;
    }

    public function channelList()
    {
        return ChannelListResource::collection($this->socketRepository->getAll());
    }
}