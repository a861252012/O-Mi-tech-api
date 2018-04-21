<?php

namespace App\Services\Room;

use App\Models\RoomDuration;
use App\Models\RoomOneToMore;
use App\Models\Users;
use App\Services\Service;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * @desc 房间类
 */
class One2OneRoomService extends Service
{
    public $rid = null;
    public $uid = null;

    public $room = [];
    public $key_room_status = "";

    const ONE2MORE = 4;

    public function __construct(Request $request)
    {
        $this->rid = $request->get('rid',isset($request->rid)?$request->rid:0);
        $this->key_room_status = "hroom_status:" . $this->rid . ":4";
        $this->room = $this->_getData();
        parent::__construct();

    }

    public function set($rid=0){
        $this->rid = $rid;
        $this->key_room_status = "hroom_status:" . $this->rid . ":4";
        $this->room = $this->_getData();
    }
    public function checkRunning(){
        return !empty($this->getRunningData());
    }
    public function getRunningData(){
        $data = $this->getData();

        foreach ($data as $k=>$ord){
            $starttime = strtotime($ord['starttime']);
            $endtime = $starttime + $ord['duration'];
            if ($this->doing($starttime,$endtime))  return $ord;
        }
        return [];
    }
    public function getData(){
        return $this->room;
    }
    public function checkPermission(){
        return !empty($this->room);
    }
    public function getHomeBookList($flashVersion) : array
    {
        $ordRooms = Redis::get('home_ord_' . $flashVersion);
        $ordRooms = str_replace(['cb(', ');'], ['', ''], $ordRooms);
        return (array)json_decode($ordRooms, true);
    }


    public function _checkPermission(){
        return Redis::exists($this->key_room_status)&& Redis::hget($this->key_room_status, "status") == '1';
    }
    private function _getData(){
        if(!$this->_checkPermission()) return [];

        if(!$ordMap = Redis::hgetall("hroom_duration:" . $this->rid . ":4")) return [];

        $resault = [];
        foreach ($ordMap as $k => $v) {
            $ord = json_decode($v, true);
            if (!$ord || $ord['status'] != 0) continue;
            array_push($resault,$ord);
        }
        $this->room = $resault;
        return $resault;
    }

    public function checkUserBuyRunning($uid){
        return !empty($this->getUserBuyRunningData($uid));
    }

    public function getUserBuyRunningData($uid){
        $ord = $this->getRunningData();
        if ($ord['reuid'] == 0) return [];

        $starttime = strtotime($ord['starttime']);
        $endtime = $starttime + $ord['duration'];
        if ($uid == $ord['reuid'] && time() >= $starttime && time() <= $endtime) return $ord;
        return $ord;
    }

}