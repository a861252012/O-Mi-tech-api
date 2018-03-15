<?php

namespace App\Services\Room;

use App\Services\Service;

/**
 *
 */
class SocketService extends Service
{
    const CHANNEL_TIME_MILLS = 330000;

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
        return empty($time) || ($time + static::CHANNEL_TIME_MILLS) < microtime(true);
    }

    public function getNextServerAvailable($uid)
    {
        $redis = $this->make('redis');
        $channels_update = collect($redis->hgetall('channel_update'));
//        $minLoadChannel = null;
        $channelIDs = collect();//可用的channel id
        $channels_update->keys()->map(function ($channelID) use (&$channels_update, &$redis, &$minLoadChannel, &$channelIDs) {
            if (!self::socketExpired($channels_update[$channelID])) {
                $channelIDs->push($channelID);
//                $channelInfo = $redis->hgetall('channel_info:' . $channelID);
//                if ($this->lessLoad($channelInfo, $minLoadChannel)) {
//                    $minLoadChannel = $channelInfo;
//                }
            }
        });
        if ($channelIDs->count() == 0) {
            throw new NoSocketChannelException('没有可用channel');
        }
        $idSelected = $channelIDs->get($uid % $channelIDs->count());
        $channelInfo = $redis->hgetall('channel_info:' . $idSelected);
        if (empty($channelInfo)) {
            throw new NoSocketChannelException('获取Socket Channel失败');
        }
        return $channelInfo;
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
     * 比较两个频道负载 return c1<c2
     * @param $c1
     * @param $c2
     * @return bool
     */
    protected function lessLoad($c1, $c2)
    {
        return !empty($c1) && (empty($c2) || $c1['total'] < $c2['total']);
    }
}