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
class One2MoreRoomService extends Service
{
    public $rid = null;
    public $uid = null;

    public $room = [];

    const ONE2MORE = 7;

    public function __construct(Request $request)
    {
        $this->rid = $request->get('rid',isset($request->rid)?$request->rid:0);
        $this->rid && $this->room = $this->_getData();
        parent::__construct();

    }

    public function set($rid=0){
        $this->rid = $rid;
        $this->room = $this->_getData();
    }
    public function checkRunning(){
        return !empty($this->getRunningData());
    }
    public function getRunningData(){
        $data = $this->getData();

        foreach ($data as $k => $ord) {
            $starttime = strtotime($ord['starttime']);
            $endtime = strtotime($ord['endtime']);
            if (time() >= $starttime && time() <= $endtime)  return $ord;
        }
        return [];
    }
    public function getHomeBookList($flashVersion) : array
    {
        $ordRooms = Redis::get('home_one_many_' . $flashVersion);
        $ordRooms = str_replace(['cb(', ');'], ['', ''], $ordRooms);
        return (array)json_decode($ordRooms, true);
    }
    public function getData(){
        return $this->room;
    }
    public function checkPermission(){
        return !empty($this->room);
    }

    public function _checkPermission(){
        return Redis::exists("hroom_status:" . $this->rid . ":7")
            && Redis::hget("hroom_status:" . $this->rid . ":7", "status") == '1';
    }
    public function _getData(){
        if(!$this->_checkPermission()) return [];

        if(!$ordMap = Redis::sMembers("hroom_whitelist_key:" . $this->rid)) return [];

        $resault = [];
        foreach ($ordMap as $k => $id) {
            $ord = Redis::hGetAll('hroom_whitelist:'.$this->rid.':'.$id);
            if (!$ord) continue;

            $ord['onetomore'] = $id;
            $ord['id'] = $id;
            array_push($resault, $ord);
        }
        return $resault;
    }

    public function doing($starttime,$endtime){
        return time() >= $starttime && time() <= $endtime;
    }

    public function checkUserBuyRunning($uid){
        return !empty($this->getUserBuyRunningData($uid));
    }

    public function checkBuyUser($uid,$onetomore){
        return !empty($this->getUserBuyData($uid,$onetomore));
    }
    public function getUserBuyData($uid,$onetomore){
        $ord = $this->getDataById($onetomore);

        if(isset($ord['uids']) && !empty(trim($ord['uids']))){
            $uidArr = explode(',',trim($ord['uids']));
            if(in_array($uid,$uidArr)) return $ord;
        }
        if(isset($ord['tickets']) && !empty(trim($ord['tickets']))) {
            $uidArr2 = explode(',',$ord['tickets']);//添加补票入口判断
            if(in_array($uid,$uidArr2)) return $ord;
        }
        return $ord;
    }
    public function getDataById($onetomore){
        $key = 'hroom_whitelist:'.$this->rid.':'.$onetomore;
       return Redis::exists($key) ? Redis::hGetAll($key) : [];
    }

    public function getUserBuyRunningData($uid){
        $ord = $this->getRunningData();

        if(isset($ord['uids']) && !empty(trim($ord['uids']))){
            $uidArr = explode(',',trim($ord['uids']));
            if(in_array($uid,$uidArr)) return $ord;
        }
        if(isset($ord['tickets']) && !empty(trim($ord['tickets']))) {
            $uidArr2 = explode(',',$ord['tickets']);//添加补票入口判断
            if(in_array($uid,$uidArr2)) return $ord;
        }
        return [];
    }


}