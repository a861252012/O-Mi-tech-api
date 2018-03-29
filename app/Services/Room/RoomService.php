<?php

namespace App\Services\Room;

use App\Models\RoomDuration;
use App\Models\RoomOneToMore;
use App\Models\Users;
use App\Services\Service;
use App\Services\User\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * @desc 房间类
 */
class RoomService extends Service
{

    public $tid = null;
    public $rid = null;
    public $uid = null;
    public $cur_login_uid = null; //当前登陆用户
    public $rtmp_host = null;
    public $rtmp_port = null;
    public $channel_id = null;
    public $finger_id = null;

    public $current_tid = null;
    public $extend_room = [];


    public $room_type = []; //一对一，一对多
    public $one2more_room = [];
    public $one2one_room = [];
    public $timecost_room = [];
    public $password_room = [];

    const TIMECOST = 6;
    const ONE2ONE = 4;
    const ONE2MORE = 8;

    const BE_KICK_OUT_TIME='beKickOutTime';
    /**
     * 检测，并重新分配socket
     * @param $uid
     * @param $rid
     * @return null
     */
    public function addRoom($uid, $rid, $view_uid)
    {
        if (Users::where('uid', $uid)->update(['rid' => $rid]) === false) return false;
        $this->uid = $uid;
        $this->rid = $rid;

        $redis = $this->make('redis');
        $hktvKey = "hvediosKtv:$rid";

        $channelInfo = $this->make('socketServices')->getNextServerAvailable($uid);
        $redis->hset($hktvKey, "channel_id", $channelInfo['id']);

        $str = "===addRoom rid===" . $rid . "===uid===" . $uid . "===serverIds size===" . json_encode($channelInfo);
        Log::channel('room')->info($str);
        //todo 后台申请主播有用到
        //todo set rtmp待测试后确定是否需要添加
        return $this->getRoom($rid, $view_uid);
    }

    /**
     * 获取房间
     * @param $rid
     * @return array|null
     */
    public function getRoom($rid, $view_uid)
    {
        $key = "hvediosKtv:$rid";
        $roomids = "hroom_ids";
        $this->cur_login_uid = $view_uid;

        $redis = $this->make('redis');
        if (!$redis->exists($key)) {
            return null;
        }
        //todo 图片接口迁移

        $this->rid = $rid;
        $room = $redis->hgetAll($key);
        $uid = $redis->hget($roomids, $rid);
        $user = resolve(UserService::class)->getUserByUid($uid);
        $view_user = resolve(UserService::class)->getUserByUid($view_uid);
        $room['user'] = $user;
        $room['room_status'] = $this->getRoomStatus($rid);

        $this->getCurrentRoomStatus();

        $timecost=isset($room['room_status'][6]['timecost'])?$room['room_status'][6]['timecost']:0;
        $discount=$redis->hget('hgroups:special:' . $view_user['vip'], 'discount')?:10;
        $room['discount'] = [
            'discount' => $discount,
            'discountValue' => ceil($timecost*$discount/10)
        ];
        return $room;
    }

    public function getRoomStatus($rid=null){
        $redis = $this->make('redis');
        return [//todo 1站加7
            1 => $redis->hgetall("hroom_status:$rid:1"),
            2 => $redis->hgetall("hroom_status:$rid:2"),
            4 => $redis->hgetall("hroom_status:$rid:4"),
            6 => Redis::hgetall("hroom_status:$rid:6"),
        ];
    }

    public function checkOne2One(){
        return $this->current_tid==4;
    }
    public function checkTimecost()
    {
        return $this->current_tid==6;
    }
    public function checkOne2More(){
        return $this->current_tid==7;
    }
    public function getTimecost(){
       if(!(
           Redis::exists("hroom_status:" . $this->rid . ":6")
            && Redis::hget("hroom_status:" . $this->rid . ":6", "status") == '1'
            && Redis::hget("htimecost:" . $this->rid, "timecost_status"))
       ) return [];

        $this->extend_room = Redis::hgetall("hroom_status:" . $this->rid . ":6");
        return $this->extend_room;
    }

    public function checkPasswdRoom() : bool
    {
        return Redis::exists("hroom_status:" . $this->rid . ":2")
            && Redis::hget("hroom_status:" . $this->rid . ":2", "status") == '1'
            && trim(Redis::hget("hroom_status:" . $this->rid . ":2", "pwd")) != '';
    }

    public function getOne2OneStart() : array
    {
        if (!(Redis::exists("hroom_status:" . $this->rid . ":4")
            && Redis::hget("hroom_status:" . $this->rid . ":4", "status") == '1')) return [];

        $ordMap = Redis::hgetall("hroom_duration:" . $this->rid . ":4");
        if ($ordMap) {
            //Log::channel('room')->info('getCurrentRoomStatus rid'.$this->rid.' uid'.$this->cur_login_uid.'hroom_duration:'.count($ordMap),$logPath);
            foreach ($ordMap as $k => $v) {
                $ord = json_decode($v, true);
                if (!$ord || $ord['status'] != 0) continue;

                $starttime = strtotime($ord['starttime']);
                $endtime = strtotime($ord['starttime']) + $ord['duration'];
                if ($this->doing($starttime,$endtime)) {
                    $this->extend_room = $ord;
                    Log::channel('room')->info('getCurrentRoomStatus rid' . $this->rid . ' uid' . $this->cur_login_uid . 'hroom_duration:' . $v);
                    return $ord;
                }
            }
        }
        return [];
    }

    public function getPwdStart(){
        $rs =  Redis::exists("hroom_status:" . $this->rid . ":2")
            && Redis::hget("hroom_status:" . $this->rid . ":2", "status") == '1'
            && trim(Redis::hget("hroom_status:" . $this->rid . ":2", "pwd")) != '';

        return $rs;
    }
    public function getTimecostPwdStart(){
        if(
            Redis::exists("hroom_status:" . $this->rid . ":6")
            && Redis::hget("hroom_status:" . $this->rid . ":6", "status") == '1'
            && Redis::hget("htimecost:" . $this->rid, "timecost_status")
            && Redis::hget("hroom_status:" . $this->rid . ":2", "status") == '1'
            && trim(Redis::hget("hroom_status:" . $this->rid . ":2", "pwd")) != ''
        ){
            $this->extend_room = Redis::hgetall("hroom_status:" . $this->rid . ":6");
            return $this->extend_room;
        }
        return [];
    }
    public function doing($starttime,$endtime){
        return time() >= $starttime && time() <= $endtime;
    }
    public function getExtendRoom(){
        return $this->extend_room;
    }

    public function getOne2MoreStart(){
        if(  Redis::exists("hroom_status:" . $this->rid . ":7")
            && Redis::hget("hroom_status:" . $this->rid . ":7", "status") == '1'
        ){
            $ordMap = Redis::sMembers("hroom_whitelist_key:" . $this->rid);
            if ($ordMap) {
                foreach ($ordMap as $k => $v) {
                    $ord = Redis::hGetAll('hroom_whitelist:'.$this->rid.':'.$v);
                    if (!$ord) continue;

                    $starttime = strtotime($ord['starttime']);
                    $endtime = strtotime($ord['endtime']);
                    if (time() >= $starttime && time() <= $endtime) {
                        $this->extend_room = $ord;
                        return $v;
                    }
                }
            }
        }
        return [];
    }

    /**
     * 获取当前房间时间点状态
     * 优化级，一对一，一对多，限制房间，普通房
     * @author raby
     */
    public function getCurrentRoomStatus()
    {
        /** @var \Redis $redis */
        $redis = $this->make('redis');

        //一对一
        if (!empty($this->getOne2OneStart())) { return $this->current_tid = 4;
        }
        //一对多
        if (!empty($this->getOne2MoreStart())) {
            if(!empty($this->extend_room)) return $this->current_tid = 8;
        }

        //时长房密码
        if (!empty($this->getTimecostPwdStart())) { return $this->current_tid=7;}

        //时长
        if (!empty($this->getTimecost())) { return $this->current_tid=6; }

        //密码房
        if (!empty($this->getPwdStart())){ return $this->current_tid = 2;}

        return $this->current_tid = 1;
    }

    /**
     * 一对多
     * @author raby
     * @return bool
     */
    public function whiteList()
    {
        $ord = $this->extend_room;
        if(isset($ord['uids']) && !empty(trim($ord['uids']))){
            $uidArr = explode(',',trim($ord['uids']));
            if(in_array($this->cur_login_uid,$uidArr)) return true;
        }
        if(isset($ord['tickets']) && !empty(trim($ord['tickets']))) {
            $uidArr2 = explode(',',$ord['tickets']);//添加补票入口判断
            if(in_array($this->cur_login_uid,$uidArr2)) return true;
        }
        return false;
    }

    /**
     *  一对一房间
     * @author raby
     * @return bool
     */
    public function checkCanIn()
    {
        $ord = $this->extend_room;
        if (!isset($ord['status'])) return false;
        if ($ord['status'] == 1 || $ord['reuid'] == 0) return false;

        $starttime = strtotime($ord['starttime']);
        $endtime = strtotime($ord['starttime']) + $ord['duration'];
        if ($this->cur_login_uid == $ord['reuid'] && time() >= $starttime && time() <= $endtime) return true;
        return false;
    }

    /**
     * 时长房间
     * @author raby
     * @return bool
     */
    public function checkDuration()
    {
        $redis = $this->make('redis');
        $room_timecost_key = "htimecost:" . $this->rid;
        $timecost_status = $redis->hget($room_timecost_key, "timecost_status");
        $timecost_watch_user = $redis->hget("htimecost_watch:" . $this->rid, $this->cur_login_uid);
        /**---主播如果是时长房收费状态，则要验证用户是否已经确认进入收费观看，如果没有，弹出收费提示。主播不需要判断，直接进入        add by ziv 2016-09-22------------*/
        if ($timecost_status && $timecost_watch_user) return true;
        return false;
    }

    /**
     * 密码房间
     * @author raby
     * @return bool
     */
    public function checkPassword()
    {
        $cookie_login_val = $_COOKIE['PHPSESSID'];

        $redis = $this->make('redis');
        $room_pass_key = "keys_room_passwd:" . $this->rid . ":" . $cookie_login_val;

        if (!$redis->exists($room_pass_key)) return false;         //没有密码
        return true;
    }

    /**
     * 踢人
     * @param $rid
     * @param $uid
     * @return bool|int
     */
    public function checkKickOut($rid, $uid)
    {
        $redis=$this->make('redis');
        $kickTime=$redis->hget("beKickOut:$rid:$uid",static::BE_KICK_OUT_TIME);
        if ($kickTime&&($start=strtotime($kickTime))){
            $timeleft=30*60+$start-time();
            if ($timeleft>0){
                return $timeleft;
            }
            return true;
        }
        return true;
    }

    public function getXOHost()
    {
        $xo_httphost=isset($_SESSION['xo_httphost'])?$_SESSION['xo_httphost']:null;
        if ($xo_httphost){
            if (!preg_match('/^https?:\/\//',$xo_httphost)){
                $xo_httphost='http://'.$xo_httphost;
            }
        }
        return $xo_httphost;
    }

    public function getPlatBackUrl($origin=0){
        $redis = $this->make('redis');
        $plat_backurl = "{}";
        if ($redis->exists("hplatforms:$origin")) {
            $hplatforms = $redis->hgetall("hplatforms:$origin");
            $plat_backurl = $hplatforms['backurl'];
        }
        return json_decode($plat_backurl, true);
    }
    public function getPlatUrl($origin=0){
        $urlList = $this->getPlatBackUrl($origin);
        $host = $this->getPlatHost();
        $data = [];
        foreach ($urlList as $key=>$url){
            $data[$key] = $host.$url;
        }
        return $data;
    }
    public function getPlatHost(){
        $httphost=isset($_SESSION['httphost'])?$_SESSION['httphost']:null;
        if ($httphost){
            if (!preg_match('/^https?:\/\//',$httphost)){
                $httphost='http://'.$httphost;
            }
        }
        return $httphost;
    }
    public function getPlatPayUrl($origin){
        $key = "";
        switch ($origin){
            case 51: $key = "xo_backurl"; break;
            case 61: $key = "l_backurl"; break;
            default: return "{}";
        }
        return $this->make('redis')->hget('hconf', $key) ?: "{}";
    }
    public function getXOPayUrl()
    {
        return $this->parseXOUrl($this->make('redis')->hget('hconf', 'xo_pay_url')) ?: '';
    }
    public function getXOHallUrl()
    {
        return $this->parseXOUrl($this->make('redis')->hget('hconf', 'xo_hall_url')) ?: '';
    }

    public function parseXOUrl($url)
    {
        $xo_httphost=$this->getXOHost();
        if (!$xo_httphost) return '';
        $url= parse_url($url);
        $xo_httphost=parse_url($xo_httphost);
        $parse_url= array_merge($url, $xo_httphost);
        if (empty($xo_httphost['port']))
            unset($parse_url['port']);
        return
            ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : 'http://')
            .((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
            .((isset($parse_url['host'])) ? $parse_url['host'] : '')
            .((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
            .((isset($parse_url['path'])) ? $parse_url['path'] : '')
            .((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
            .((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
            ;
    }

    /*
     * app和pc  添加一对多房间。
     */
    public function addOnetomore($data)
    {

        if (empty($data['origin']))    $data['origin'] = 11;
        if (!in_array($data['duration'], array(20,25,30,35,40,45,50,55,60))) return ['status' => 9, 'msg' => '请求错误'];
        if ($data['points']>99999 || $data['points']<=0) return ['status' => 3, 'msg' => '金额超出范围'];

        if (empty($data['mintime']) || empty($data['duration'])) return ['status' => 4, 'msg' => '请求错误'];
        $start_time = date("Y-m-d H:i:s", strtotime($data['mintime'] . ' ' . $data['hour'] . ':' . $data['minute'] . ':00'));

        if (date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($start_time))) return ['status' => 6, 'msg' => '不能设置过去的时间'];
        $endtime = date('Y-m-d H:i:s', strtotime($start_time) + $data['duration'] * 60);

        if (!$this->notSetRepeat($start_time, $endtime)) return ['status' => 2, 'msg' => '你这段时间和一对一或一对多有重复的房间'];


        $redis = $this->make('redis');

        $uids = '';
        $tickets = 0;



        //如果结束时间在记录之前并且未结速，则处理。否则忽略
        $now = date('Y-m-d H:i:s');
        $lastRoom = RoomOneToMore::where('uid', $data['uid'])->where('endtime', '>', $now)->where('status', 0)->orderBy('endtime', 'asc')->first();
        if (!$lastRoom || strtotime($lastRoom->starttime) > strtotime($endtime)) {
            //当天消费,并且只能向后设置，固不用判断时间大于开始时间情况
            $macro_starttime = strtotime($start_time);
            $h = date('H');
            $etime = '';
            if ($h >= 6) {
                $etime = strtotime(date('Y-m-d')) + 30 * 3600;
            } else {
                $etime = strtotime(date('Y-m-d')) + 6 * 3600;
            }
            if ($macro_starttime < $etime) {
                $user_send_gite = $redis->hGetAll('one2many_statistic:' . Auth::id());
                if ($user_send_gite) {
                    foreach ($user_send_gite as $k => $v) {
                        if ($v >= $data['points']) {
                            $tickets += 1;
                            $uids .= $k . ",";
                        }
                    }
                    $uids = substr($uids, 0, -1);
                }
            }
        }
      /*  if (empty($uids)) {
            return ['status' => 2, 'msg' => '没有用户满足送礼数，不允许创建房间'];
        }*/


        //$points = $room_config['timecost'];
        $oneToMoreRoom = new RoomOneToMore();
        $oneToMoreRoom->created = date('Y-m-d H:i:s');
        $oneToMoreRoom->uid = $data['uid'];
        $oneToMoreRoom->roomtid = $data['tid'];
        $oneToMoreRoom->starttime = $start_time;
        $oneToMoreRoom->duration = $data['duration'] * 60;
        $oneToMoreRoom->endtime = $endtime;
        $oneToMoreRoom->status = 0;
        $oneToMoreRoom->tickets = $tickets;
        $oneToMoreRoom->points = $data['points'];
        $oneToMoreRoom->origin = $data['origin'];
        $oneToMoreRoom->save();


        if ($uids) {
            $uidArr = explode(',', $uids);
            $insertArr = [];
            foreach ($uidArr as $k => $v) {
                $temp = [];
                $temp['onetomore'] = $oneToMoreRoom->id;
                $temp['rid'] = $data['uid'];
                $temp['type'] = 2;
                $temp['starttime'] = $start_time;
                $temp['endtime'] = $endtime;
                $temp['duration'] = $data['duration'] * 60;
                $temp['points'] = $data['points'];
                $temp['uid'] = $v;
                $temp['origin'] = $data['origin'];
                array_push($insertArr, $temp);
            }
            DB::table('video_user_buy_one_to_more')->insert($insertArr);
        }

        $duroom = $oneToMoreRoom;
        $redis->sAdd("hroom_whitelist_key:" . $duroom['uid'], $duroom->id);

        $temp = [
            'starttime' => $duroom['starttime'],
            'endtime' => $duroom['endtime'],
            'uid' => $duroom['uid'],
            'nums' => $tickets,
            'uids' => $uids,
            'points' => $data['points'],
        ];
        $rs = $this->make('redis')->hmset('hroom_whitelist:' . $duroom['uid'] . ':' . $duroom->id, $temp);

        Log::channel('room')->info('OneToMore'.$duroom->toJson());
        return ['status' => 1, 'msg' => '添加成功'];

    }

    /**
     * 时段房间互拆（一对一，一对多）
     * 返回 true 不重叠 false重叠
     */
    public function notSetRepeat($start_time, $endtime)
    {
        $now = date('Y-m-d H:i:s');
        //时间，是否和一对一有重叠
        $data = RoomDuration::where('status', 0)->where('uid', Auth::id())
            ->orderBy('starttime', 'DESC')
            ->get()->toArray();

        $temp_data = $this->array_column_multi($data, ['starttime', 'endtime']);
        if (!$this->checkActiveTime($start_time, $endtime, $temp_data)) return false;

        //时间，是否和一对多有重叠
        $data = RoomOneToMore::where('status', 0)->where('uid', Auth::id())->get()->toArray();
        $temp_data = $this->array_column_multi($data, ['starttime', 'endtime']);
        if (!$this->checkActiveTime($start_time, $endtime, $temp_data)) return false;
        return true;
    }


    /**
     * @param string $stime
     * @param string $etime
     * @param array $data
     * @return bool false重叠 true不重叠
     */
    public function checkActiveTime($stime = '', $etime = '', $data = [])
    {
        $stime = strtotime($stime);
        $etime = strtotime($etime);

        $flag = true;
        foreach ($data as $k => $v) {
            //开始时间在区间之内
            if ($stime >= strtotime($v['starttime']) && $stime <= strtotime($v['endtime'])) {
                $flag = false;
                break;
            }
            //结束时间在区间之内
            if ($etime >= strtotime($v['starttime']) && $etime <= strtotime($v['endtime'])) {
                $flag = false;
                break;
            }
            //包含
            if ($stime <= strtotime($v['starttime']) && $etime >= strtotime($v['endtime'])) {
                $flag = false;
                break;
            }
        }
        return $flag;
    }

    function array_column_multi(array $input, array $column_keys)
    {
        $result = [];
        $column_keys = array_flip($column_keys);
        foreach ($input as $key => $el) {
            $result[$key] = array_intersect_key($el, $column_keys);
        }
        return $result;
    }


    /*
     * app和pc，添加一对一房间
     */
    public function addOnetoOne($data)
    {

        if (empty($data['tid']) || empty($data['mintime']) || empty($data['duration']) || empty($data['points'])) return ['status' => 0, 'msg' => '请求错误'];
        $start_time = date("Y-m-d H:i:s", strtotime($data['mintime'] . ' ' . $data['hour'] . ':' . $data['minute'] . ':00'));
        $theday = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") + 7, date("Y")));

        if ($theday < date("Y-m-d H:i:s", strtotime($start_time))) return ['status' => 0, 'msg' => '只能设置未来七天以内'];

        if (date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($start_time))) return ['status' => 0, 'msg' => '不能设置过去的时间'];

        $durationRoom = new RoomDuration();
        $durationRoom->created = date('Y-m-d H:i:s');
        $durationRoom->uid = Auth::id();
        $durationRoom->roomtid = $data['tid'];
        $durationRoom->starttime = $start_time;
        $durationRoom->duration = $data['duration'] * 60;
        $durationRoom->status = 0;
        $durationRoom->points = $data['points'];


        $endtime = date('Y-m-d H:i:s', strtotime($start_time) + $durationRoom->duration);
        if ($this->notSetRepeat($start_time, $endtime)) {
            $durationRoom->save();
            $this->set_durationredis($durationRoom);
            return ['status' => 1, 'msg' => '添加成功'];
        } else {
            return ['status' => 0, 'msg' => '你这段时间有重复的房间'];
        }


    }

    /*
     * 设置时长房间的redis
     */

    public function set_durationredis($durationRoom)
    {
        if (empty($durationRoom)) return false;
        $keys = 'hroom_duration:' . $durationRoom->uid . ':' . $durationRoom->roomtid;
        $arr = $durationRoom->find($durationRoom->id)->toArray();
        unset($arr['endtime']);
        Redis::hSet($keys, $arr['id'], json_encode($arr));
        return true;
    }

    /*
     * 删除一对一 by  desmond
     */

    public  function delOnetoOne($data){

        $room = RoomDuration::find($data);
        if (!$room) return ['status' => 0, 'msg' => '房间不存在'];
        if ($room->uid != Auth::id()) return ['status' => 0, 'msg' => '只能删除自己房间'];//只能删除自己房间
        if ($room->status == 1)  return  ['status' => 0, 'msg' => '房间已经删除'];
        if ($room->reuid != 0) {
            return  ['status' => 0, 'msg' => '房间已经被预定，不能删除！'];
        }
        $this->make('redis')->hdel('hroom_duration:' . $room->uid . ':' . $room->roomtid, $room->id);//删除对应的redis
        $room->delete();
        return  ['status' => 1, 'msg' => '删除成功'];
    }


}