<?php

namespace App\Services\Room;

use App\Services\Service;
use App\Services\SocketProxyService;

/**
 *
 */
class SocketService extends Service
{
    const CHANNEL_TIME_MILLS = 60000;

    protected $socketProxyService;

    public function __construct(SocketProxyService $socketProxyService)
    {
        $this->socketProxyService = $socketProxyService;
    }

    public function getServer($channelID)
    {
        $redis = $this->make('redis');
        //check alive
        $time = $redis->hget('channel_update', $channelID);
        if (static::socketExpired($time)) {
            //socket挂了
            return false;
        }
        $server = $redis->hgetall('channel_info:' . $channelID);
        return $server;
    }

    public static function socketExpired($time)
    {
        return empty($time) || ($time + static::CHANNEL_TIME_MILLS) < ceil(microtime(true)*1000);
    }

    /**
     * @return mixed
     * @throws NoSocketChannelException
     */
    public function getNextServerAvailable($isHost = 0)
    {
        $redis = resolve('redis');
        $channels_update = collect($redis->hgetall('channel_update'));

//        $channelIDs = collect();//可用的channel id
        $channels_update->keys()->map(function ($channelID) use (&$channels_update, &$redis, &$minLoadChannel, &$channelIDs, &$isHost) {
            if ($channelID >= 900 && !$isHost) {
                return;
            }
            if (!self::socketExpired($channels_update[$channelID])) {
//                $channelIDs->push($channelID);
                $channelInfo = $redis->hgetall('channel_info:' . $channelID);
                if (empty($channelInfo['host'])) {
                    return;
                }
                //Log::info("channel：" . json_encode($channelInfo));
                if ($this->lessLoad($channelInfo, $minLoadChannel)) {
                    $minLoadChannel = $channelInfo;

                    /* 取得socket proxy 列表 */
                    $list = $this->socketProxyService->proxyList();
                    $minLoadChannel['host'] = collect($list)->implode('host', ',');
                }
            }
        });

//        if ($channelIDs->count() == 0) {
        if (!$minLoadChannel) {
            throw new NoSocketChannelException('没有可用channel');
        }
//        $idSelected = $channelIDs->get($uid % $channelIDs->count());
//        $channelInfo = $redis->hgetall('channel_info:' . $idSelected);
        if (empty($minLoadChannel)) {
            throw new NoSocketChannelException('获取Socket Channel失败');
        }

        return $minLoadChannel;
    }
    /**
     * @return mixed
     * pc端接口维持原先用法
     * @throws NoSocketChannelException
     */
    public function getNextServerAvailablepc($isHost = 0)
    {
        $redis = resolve('redis');
        $channels_update = collect($redis->hgetall('channel_update'));
        $minLoadChannel = null;
//        $channelIDs = collect();//可用的channel id
        $channels_update->keys()->map(function ($channelID) use (&$channels_update, &$redis, &$minLoadChannel, &$channelIDs, &$isHost) {
            if ($channelID >= 900 && !$isHost) {
                return;
            }
            if (!self::socketExpired($channels_update[$channelID])) {
//                $channelIDs->push($channelID);
                $channelInfo = $redis->hgetall('channel_info:' . $channelID);
                if (empty($channelInfo['host'])) {
                    return;
                }
                //Log::info("channelPC：" . json_encode($channelInfo));
                if ($this->lessLoad($channelInfo, $minLoadChannel)) {
                    $minLoadChannel = $channelInfo;
                }
            }
        });
//        if ($channelIDs->count() == 0) {
        if (!$minLoadChannel) {
            throw new NoSocketChannelException('没有可用channel');
        }
//        $idSelected = $channelIDs->get($uid % $channelIDs->count());
//        $channelInfo = $redis->hgetall('channel_info:' . $idSelected);
        if (empty($minLoadChannel)) {
            throw new NoSocketChannelException('获取Socket Channel失败');
        }

//        dd($minLoadChannel);
        return $minLoadChannel;
    }
    /**
     * 比较两个频道负载 return c1<c2
     * @param $c1
     * @param $c2
     * @return bool
     */
    protected function lessLoad($c1, $c2)
    {
        return !empty($c1) && (empty($c2) || $c1['total'] < $c2['total']);
    }

    public function getActiveChannelIDs()
    {
        $redis = $this->make('redis');
        $channelIDs = collect();
        $channels_update = collect($redis->hgetall('channel_update'));
        $channels_update->keys()->map(function ($channelID) use (&$channels_update, &$redis, &$minLoadChannel, &$channelIDs) {
            if (!self::socketExpired($channels_update[$channelID])) {
                $channelIDs->push($channelID);
            }
        });
    }

    /**
     * 隨機取線
     * @param $data 傳入hosts
     * @param $count 取得數量
     */
    private function randomProxy($data, $count)
    {
        $hosts = collect(explode(',', $data));
        if ($hosts->isEmpty()) {
            return '';
        }

        if ($hosts->count() < $count) {
            return $hosts->implode(',');
        }

        return $hosts->random(3)->implode(',');
    }
}