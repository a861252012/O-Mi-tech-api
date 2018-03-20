<?php

namespace App\Services\Room;

use App\Models\Users;
use App\Services\User\UserService;
use Core\Request;
use App\Services\Service;
use Illuminate\Container\Container;

/**
 *  @desc 房间类
 */
class RoomService extends Service
{

    public $tid = null;
    public $rid = null;
    public $current_tid = null;
    public $uid = null;
    public $cur_login_uid = null; //当前登陆用户
    public $rtmp_host = null;
    public $rtmp_port = null;
    public $channel_id = null;
    public $finger_id = null;
    public $extend_room = [];

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
        $this->make('systemServer')->logResult($str, "addRoom.log");
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
        $room['user'] = $user;
        $room['room_status'] = [//todo 1站加7
            1 => $redis->hgetall("hroom_status:$rid:1"),
            2 => $redis->hgetall("hroom_status:$rid:2"),
            4 => $redis->hgetall("hroom_status:$rid:4"),
            6 => $redis->hgetall("hroom_status:$rid:6"),
        ];
        $timecost=isset($room['room_status'][6]['timecost'])?$room['room_status'][6]['timecost']:0;
        $discount=$redis->hget('hgroups:special:' . $user['vip'], 'discount')?:10;
        $room['discount'] = [
            'discount' => $discount,
            'discountValue' => ceil($timecost*$discount/10)
        ];
        return $room;
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
        $logPath = BASEDIR . '/app/logs/room'.date('Y-m').'.log';

        //一对一
        if ($redis->exists("hroom_status:" . $this->rid . ":4") && $redis->hget("hroom_status:" . $this->rid . ":4", "status") == '1') {
            $ordMap = $redis->hgetall("hroom_duration:" . $this->rid . ":4");
            if ($ordMap) {
                //$this->make('systemServer')->logResult('getCurrentRoomStatus rid'.$this->rid.' uid'.$this->cur_login_uid.'hroom_duration:'.count($ordMap),$logPath);
                foreach ($ordMap as $k => $v) {
                    $ord = json_decode($v, true);
                    if (!$ord || $ord['status'] != 0) continue;

                    $starttime = strtotime($ord['starttime']);
                    $endtime = strtotime($ord['starttime']) + $ord['duration'];
                    if (time() >= $starttime && time() <= $endtime) {
                        $this->make('systemServer')->logResult('getCurrentRoomStatus rid' . $this->rid . ' uid' . $this->cur_login_uid . 'hroom_duration:' . $v, $logPath);
                        $this->extend_room = $ord;
                        return $this->current_tid = 4;
                    }
                }
            }
        }
        //一对多
        if ($redis->exists("hroom_status:" . $this->rid . ":7") && $redis->hget("hroom_status:" . $this->rid . ":7", "status") == '1') {
            $ordMap = $redis->sMembers("hroom_whitelist_key:" . $this->rid);
            if ($ordMap) {
                foreach ($ordMap as $k => $v) {
                    $ord = $redis->hGetAll('hroom_whitelist:'.$this->rid.':'.$v);
                    if (!$ord) continue;

                    $starttime = strtotime($ord['starttime']);
                    $endtime = strtotime($ord['endtime']);
                    if (time() >= $starttime && time() <= $endtime) {
                        $this->extend_room = $ord;
                        $this->extend_room['onetomore']= $v;
                        $this->make('systemServer')->logResult('getCurrentRoomStatus rid' . $this->rid . ' uid' . $this->cur_login_uid . 'hroom_whitelist:'.$this->rid.':'.$v, $logPath);
                        return $this->current_tid = 8;
                    }
                }
            }
        }
        //时长房
        if ($redis->exists("hroom_status:" . $this->rid . ":6") && $redis->hget("hroom_status:" . $this->rid . ":6", "status") == '1' && trim($redis->hget("hroom_status:" . $this->rid . ":2", "pwd")) != '') {
            if ($redis->hget("htimecost:" . $this->rid, "timecost_status")) {
                if ($redis->exists("hroom_status:" . $this->rid . ":2") && $redis->hget("hroom_status:" . $this->rid . ":2", "status") == '1' && trim($redis->hget("hroom_status:" . $this->rid . ":2", "pwd")) != ''){
                    return $this->current_tid = 7;
                }
                return $this->current_tid = 6;
            }
        }
        //密码房
        if ($redis->exists("hroom_status:" . $this->rid . ":2") && $redis->hget("hroom_status:" . $this->rid . ":2", "status") == '1' && trim($redis->hget("hroom_status:" . $this->rid . ":2", "pwd")) != '') return $this->current_tid = 2;

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

//    public function getPlatUrl($origin=""){
//        $key = "";
//        switch ($origin){
//            case 51: $key = "xo_backurl"; break;
//            case 61: $key = "l_backurl"; break;
//            default: return "{}";
//        }
//        return $this->make('redis')->hget('hconf',$key) ?:"{}";
//    }
    public function getPlatBackUrl($origin=0){
        $redis = $this->make('redis');
        $plat_backurl = "{}";
        if($redis->exists("hplatforms:$origin")){
            $hplatforms = $redis->hgetall("hplatforms:$origin");
            $plat_backurl = $hplatforms['backurl'];
        }
        return json_decode($plat_backurl,true);
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
        return $this->make('redis')->hget('hconf',$key) ?:"{}";
    }
    public function getXOPayUrl()
    {
        return $this->parseXOUrl($this->make('redis')->hget('hconf','xo_pay_url'))? : '';
    }
    public function getXOHallUrl()
    {
        return $this->parseXOUrl($this->make('redis')->hget('hconf','xo_hall_url'))? : '';
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

}