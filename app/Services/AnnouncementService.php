<?php
/**
 * 登入公告 服務
 * @author Weine
 * @date 2020/1/2
 */

namespace App\Services;


use App\Facades\SiteSer;
use App\Http\Resources\AnnouncementResource;
use App\Repositories\AnnouncementRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AnnouncementService
{
    /* apcu快取存活時間 */
    const APCU_TTL = 1;

    protected $announcementRepository;

    public function __construct(AnnouncementRepository $announcementRepository)
    {
        $this->announcementRepository = $announcementRepository;
    }

    /* 取得登入公告 */
    public function  getLoginMsgByDevice($device)
    {
        $loginmsgKey = 'hloginmsg:' . SiteSer::siteId();

        $announcements = Cache::remember("$loginmsgKey:list", self::APCU_TTL, function() use($loginmsgKey) {
            /* 從Redis取得 */
            $data = json_decode(Redis::hget($loginmsgKey, 'list'));

            /* 如無資料則從DB取得，並建立Redis資料 */
            if(empty($data)) {
                $data = AnnouncementResource::collection($this->announcementRepository->getListForActive(SiteSer::siteId()))->jsonSerialize();
                Redis::hSet($loginmsgKey, 'list', json_encode($data));
            }

            return $data ?? '';
        });

        /* 依裝置類型過濾 */
        return collect($announcements)->where('device', $device)->when(request()->has('blank'), function ($item) {
            return $item->where('blank', request()->get('blank'));
        })->values()->map(function($item, $key) {
            return collect($item)->except(['device']);
        })->all();
    }
}