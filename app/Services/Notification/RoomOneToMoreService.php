<?php
/**
 * 一對多房間 服務
 * @author Weine
 * @date 2020-10-20
 */

namespace App\Services\Notification;


use App\Http\Resources\Notification\NotificationShowResource;
use App\Repositories\RoomOneToMoreRepository;

class RoomOneToMoreService
{
    protected $roomOneToMoreRepository;

    public function __construct(RoomOneToMoreRepository $roomOneToMoreRepository)
    {
        $this->roomOneToMoreRepository = $roomOneToMoreRepository;
    }

    /* 取得未來五分鐘內即將開一對多主播列表 */
    public function getShowInFiveMins()
    {
        return NotificationShowResource::collection($this->roomOneToMoreRepository->getListInFiveMinutes());
    }
}