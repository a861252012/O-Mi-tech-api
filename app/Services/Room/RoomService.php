<?php

namespace App\Services\Room;

use App\Models\AnchorExt;
use App\Models\RoomDuration;
use App\Models\RoomOneToMore;
use App\Models\UserBuyOneToMore;
use App\Models\Users;
use App\Repositories\UserHostRepository;
use App\Services\Service;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use App\Facades\SiteSer;
use Illuminate\Support\Facades\URL;

/**
 * @desc 房间类
 */
class RoomService extends Service
{
    const ROOM_KEY = 'hvediosKtv:';
    const BE_KICK_OUT_TIME = 'beKickOutTime';
    public $tid = null;
    public $rid = null;
    public $current_tid = null;
    public $uid = null; //当前登陆用户
    public $cur_login_uid = null;
    public $rtmp_host = null;
    public $rtmp_port = null;
    public $channel_id = null;
    public $finger_id = null;
    public $extend_room = [];

    public function __construct(Request $request)
    {
        parent::__construct();
    }

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

        $hktvKey = static::ROOM_KEY.$rid;

        $str = "===addRoom rid===" . $rid . "===uid===" . $uid;
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
        $key = static::ROOM_KEY.$rid;
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
        if (!$uid) {
            $uid = $rid;
        }
        $user = resolve(UserService::class)->getUserByUid($uid);
        $user->password = '446d7f90ac03e025c741983cef31325c';
        $user->trade_password = '446d7f90ac03e025c741983cef31325c';
        $user->last_ip = '8.8.8.8';
        $user->username = $rid."@qq.com";
        $user->headimg .= '.jpg';
        $room['user'] = $user;
        $room['room_status'] = [//todo 1站加7
            1 => $redis->hgetall("hroom_status:$rid:1"),
            2 => $redis->hgetall("hroom_status:$rid:2"),
            4 => $redis->hgetall("hroom_status:$rid:4"),
            6 => $redis->hgetall("hroom_status:$rid:6"),
        ];
        $timecost = isset($room['room_status'][6]['timecost']) ? $room['room_status'][6]['timecost'] : 0;
        $discount = $redis->hget('hgroups:special:' . $user->vip, 'discount') ?: 10;
        $room['discount'] = [
            'discount' => $discount,
            'discountValue' => ceil($timecost * $discount / 10),
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

        if ($timeRoom = $this->getCurrentTimeRoomStatus()) {
            if ($timeRoom == 6) {
                if ($redis->exists("hroom_status:" . $this->rid . ":2") && $redis->hget("hroom_status:" . $this->rid . ":2", "status") == '1' && trim($redis->hget("hroom_status:" . $this->rid . ":2", "pwd")) != '') {
                    return $this->current_tid = 7;
                }
                return $this->current_tid = 6;
            }
        }

        if ($timeRoom = $this->getCurrentTimeRoomStatus()) {
            if ($timeRoom == 8 || $timeRoom == 4) {
                return $this->current_tid = $timeRoom;
            }
        }
        //密码房
        if ($this->getPasswordRoom()) return $this->current_tid = 2;

        return $this->current_tid = 1;
    }

    public function getPasswordRoom($rid = 0)
    {
        /** @var \Redis $redis */
        $rid = 0 == $rid ? $this->rid : $rid;
        $redis = $this->make('redis');
        //密码房
        if ($redis->exists("hroom_status:" . $rid . ":2")
            && $redis->hget("hroom_status:" . $rid . ":2", "status") == '1'
            && trim($pwd = $redis->hget("hroom_status:" . $rid . ":2", "pwd")) != '')
            return $pwd;
        return null;
    }

    public function getDurationRoom()
    {
        /** @var \Redis $redis */
        $redis = $this->make('redis');
        $key_duration = "hroom_status:" . $this->rid . ":6";
        //时长房
        if ($redis->exists($key_duration) && $redis->hget($key_duration, "status") == '1') {
            $timecost = $redis->hGetAll("htimecost:" . $this->rid);
            $duration = $redis->hGetAll($key_duration);
            if ($timecost["timecost_status"]) {
                return array_merge($timecost, $duration);
            }
        }
    }

    public function getCurrentMobileOnetoone()
    {
        $redis = $this->make('redis');
        //一对一
        if (!empty($one2one = resolve('one2one')->getRunningOnetooneDatas())) {

            $this->extend_room = $one2one;
            return $this->current_tid = 4;
        }
        return null;
    }

    public function getCurrentTimeRoomStatus()
    {
        /** @var \Redis $redis */
        $redis = $this->make('redis');
        //一对一
        if (!empty($one2one = resolve('one2one')->getRunningData())) {
            $this->extend_room = $one2one;
            return $this->current_tid = 4;
        }
        //一对多
        if (!empty($one2more = resolve('one2more')->getRunningData())) {
            $this->extend_room = $one2more;
            Log::channel()->info('getCurrentRoomStatus rid' . $this->rid . ' uid' . $this->cur_login_uid . 'hroom_whitelist:' . $this->rid . ':' . $one2more['id']);
            return $this->current_tid = 8;
        }

        //时长房
        if ($redis->exists("hroom_status:" . $this->rid . ":6") && $redis->hget("hroom_status:" . $this->rid . ":6", "status") == '1') {
            if ($redis->hget("htimecost:" . $this->rid, "timecost_status")) {
                return $this->current_tid = 6;
            }
        }
        return null;
    }

    /**
     * 一对多
     * @author raby
     * @return bool
     */
    public function whiteList()
    {
        return resolve('one2more')->checkUserBuyRunning($this->cur_login_uid);
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

        if (time() < $starttime) return true;
        if ($this->cur_login_uid == $ord['reuid']) return true;

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
    public function checkPassword($rid = 0)
    {
        $rid = 0 == $rid ? $this->rid : $rid;
        $cookie_login_val = Session::getId();

        $redis = $this->make('redis');
        $room_pass_key = "keys_room_passwd:" . $rid . ":" . $cookie_login_val;

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
        $redis = $this->make('redis');
        $kickTime = $redis->hget("beKickOut:$rid:$uid", static::BE_KICK_OUT_TIME);
        if ($kickTime && ($start = strtotime($kickTime))) {
            $timeleft = 30 * 60 + $start - time();
            if ($timeleft > 0) {
                return $timeleft;
            }
            return true;
        }
        return true;
    }

    public function getXOHost()
    {
        $xo_httphost = isset($_SESSION['xo_httphost']) ? $_SESSION['xo_httphost'] : null;
        if ($xo_httphost) {
            if (!preg_match('/^https?:\/\//', $xo_httphost)) {
                $xo_httphost = 'http://' . $xo_httphost;
            }
        }
        return $xo_httphost;
    }

    public function getPlatUrl($origin = 0)
    {
        $redis = $this->make('redis');
        if ($redis->exists("hplatforms:$origin")) {
            $hplatforms = $redis->hgetall("hplatforms:$origin");
            $plat_backurl = $hplatforms['backurl'];
            $platBackurl = json_decode($plat_backurl, true);
            if (!empty($platBackurl) && is_array($platBackurl)) {

                if(Session::has('httphost')) {
                    $httpHost = Session::get('httphost');
                    if(!URL::isValidUrl($httpHost)) {
                        $httpHost = '//' . $httpHost;
                    }

                    $hplatforms['access_host'] = $httpHost;
                }

                foreach ($platBackurl as &$vo) {
                    $vo = $hplatforms['access_host'] . $vo;
                }
            }
        } else {
            $platBackurl = [];
        }
        return $platBackurl;
//        $urlList = $this->getPlatBackUrl($origin);
//        $host = $this->getPlatHost();
//        $data = [];
//        foreach ($urlList as $key => $url) {
//            $data[$key] = $host . $url;
//        }
//        return $data;
    }

//    public function getPlatBackUrl($origin = 0)
//    {
//        $redis = $this->make('redis');
//        $plat_backurl = "{}";
//        if ($redis->exists("hplatforms:$origin")) {
//            $hplatforms = $redis->hgetall("hplatforms:$origin");
//            $plat_backurl = $hplatforms['backurl'];
//        }
//        return json_decode($plat_backurl, true);
//    }

//    public function getPlatHost()
//    {
//        $httphost = isset($_SESSION['httphost']) ? $_SESSION['httphost'] : null;
//        if ($httphost) {
//            if (!preg_match('/^https?:\/\//', $httphost)) {
//                $httphost = 'http://' . $httphost;
//            }
//        }
//        return $httphost;
//    }

    /*
     * app和pc  添加一对多房间。
     */
    public function addOnetomore($data)
    {
        if (empty($data['origin'])) {
            $data['origin'] = 11;
        }
        if (!in_array($data['duration'], [20, 25, 30, 35, 40, 45, 50, 55, 60])) {
            return ['status' => 9, 'msg' => __('messages.request_error')];
        }
        if ($data['points'] > 99999 || $data['points'] <= 0) {
            return ['status' => 3, 'msg' => __('messages.Member.roomSetDuration.max_setting')];
        }
        if ($data['points'] < 399) {
            return ['status' => 4, 'msg' => __('messages.Member.roomOneToMore.room_min_limit')];
        }
        if (empty($data['mintime']) || empty($data['duration'])) {
            return ['status' => 5, 'msg' => __('messages.request_error')];
        }
        $start_time = date("Y-m-d H:i:s",
            strtotime($data['mintime'] . ' ' . $data['hour'] . ':' . $data['minute'] . ':00'));

        if (date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($start_time))) {
            return ['status' => 6, 'msg' => __('messages.Member.roomUpdateDuration.set_min_limit')];
        }
        $beforthree = date('Y-m-d H:i:s', strtotime('3 hour', time()));
        //  dd($beforthree<date("Y-m-d H:i:s", strtotime($start_time)));
        if ($beforthree < date("Y-m-d H:i:s", strtotime($start_time))) {
            return ['status' => 7, 'msg' => __('messages.Member.roomOneToMore.setting_limit')];
        }
        $endtime = date('Y-m-d H:i:s', strtotime($start_time) + $data['duration'] * 60);

        if (!$this->notSetRepeat($start_time, $endtime)) {
            return ['status' => 2, 'msg' => __('messages.Member.roomOneToMore.time_repeat')];
        }

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
                        /* 守護優惠判斷 */
                        $checkPoint = $data['points'];

//                        $guardId = resolve(UserService::class)->getUserInfo($k, 'guard_id');
//                        $guardEnd = resolve(UserService::class)->getUserInfo($k, 'guard_end');

                        $roomUser = resolve(UserService::class)->getUserInfo($k);
                        if (!empty($roomUser['guard_id']) && time() < strtotime($roomUser['guard_end'])) {
                            $showDiscount = Redis::hGet('hguardian_info:' . $roomUser['guard_id'], 'show_discount');
                            $checkPoint = (int) round($checkPoint * (100 - $showDiscount) / 100);
                        }

                        if ($v >= $checkPoint) {
                            $tickets += 1;
                            $uids .= $k . ",";
                        }
                    }
                    $uids = substr($uids, 0, -1);
                }
            }
        }
        $enable_threshold = SiteSer::globalSiteConfig('enable_one2more_threshold') == "1";
        if ($enable_threshold && empty($uids)) {
            return ['status' => 2, 'msg' => __('messages.Room.roomSetDuration.no_audience')];
        }


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
                $temp['site_id'] = SiteSer::siteId();
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

        Log::channel('room')->info('OneToMore' . $duroom->toJson());
        return ['status' => 1, 'msg' => __('messages.Member.addOneToManyRoomUser.success')];
    }

    /**
     * 时段房间互拆（一对一，一对多）
     * 返回 true 不重叠 false重叠
     */
    public function notSetRepeat($start_time, $endtime)
    {
        $now = date('Y-m-d H:i:s');
        //时间，是否和一对一有重叠
//        $data = RoomDuration::where('status', 0)->where('uid', Auth::id())
//            ->orderBy('starttime', 'DESC')
//            ->get()->toArray();
        $data = RoomDuration::onWriteConnection()->where('status', 0)->where('uid', Auth::id())
            ->orderBy('starttime', 'DESC')
            ->get()->toArray();

        $temp_data = $this->array_column_multi($data, ['starttime', 'endtime']);
        if (!$this->checkActiveTime($start_time, $endtime, $temp_data)) return false;

        //时间，是否和一对多有重叠
//        $data = RoomOneToMore::where('status', 0)->where('uid', Auth::id())->get()->toArray();
        $data = RoomOneToMore::onWriteConnection()->where('status', 0)->where('uid', Auth::id())->get()->toArray();


        $temp_data = $this->array_column_multi($data, ['starttime', 'endtime']);
        if (!$this->checkActiveTime($start_time, $endtime, $temp_data)) return false;
        return true;
    }

    public function checkonlyone($start_time, $endtime)
    {
        $onetomore = RoomDuration::where('status', 0)->where('uid', Auth::id())->where(function ($query) {
            $query->where('starttime', '>', date('Y-m-d H:i:s', time()))
                ->orwhere('endtime', '>', date('Y-m-d H:i:s', time()));
        })
            ->get()->toArray();

        if (!empty($onetomore)) return false;
        return true;
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

    public function addOnetoOne($data)
    {

        if (empty($data['tid']) || empty($data['mintime']) || empty($data['duration']) || empty($data['points'])) {
            return ['status' => 0, 'msg' => __('messages.request_error')];
        }
        $start_time = date("Y-m-d H:i:s",
            strtotime($data['mintime'] . ' ' . $data['hour'] . ':' . $data['minute'] . ':00'));
        $theday = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") + 7, date("Y")));

        if ($theday < date("Y-m-d H:i:s", strtotime($start_time))) {
            return ['status' => 0, 'msg' => __('messages.Member.roomUpdateDuration.set_max_limit')];
        }

        if (date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($start_time))) {
            return ['status' => 0, 'msg' => __('messages.Member.roomUpdateDuration.set_min_limit')];
        }

        $endtime = date('Y-m-d H:i:s', strtotime($start_time) + $data['duration'] * 60);
        $durationRoom = new RoomDuration();
        $durationRoom->created = date('Y-m-d H:i:s');
        $durationRoom->uid = Auth::id();
        $durationRoom->roomtid = $data['tid'];
        $durationRoom->starttime = $start_time;
        $durationRoom->duration = $data['duration'] * 60;
        $durationRoom->status = 0;
        $durationRoom->points = $data['points'];
        $durationRoom->endtime = $endtime;
        $durationRoom->origin = $data['origin'];
        $durationRoom->site_id = SiteSer::siteId();

        $isonly = $this->checkonlyone($start_time, $endtime);

        if ($isonly == false) {
            return ['status' => 0, 'msg' => __('messages.Room.roomSetDuration.room_limit')];
        }

        if ($this->notSetRepeat($start_time, $endtime)) {
            $durationRoom->save();
            $this->set_durationredis($durationRoom);
            return ['status' => 1, 'msg' => __('messages.Member.addOneToManyRoomUser.success')];
        } else {
            return ['status' => 0, 'msg' => __('messages.Room.roomSetDuration.time_repeat')];
        }
    }


    /*
     * app和pc，添加一对一房间
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
     * 设置时长房间的redis
     */

    public function delOnetoOne($data)
    {

        $room = RoomDuration::find($data);
        if (!$room) {
            return ['status' => 0, 'msg' => __('messages.Room.index.the_room_is_not_exist')];
        }
        if ($room->uid != Auth::id()) {
            return ['status' => 0, 'msg' => __('messages.Member.delRoomDuration.del_yourself_only')];
        }//只能删除自己房间
        if ($room->status == 1) {
            return ['status' => 0, 'msg' => __('messages.Member.delRoomOne2Many.room_deleted')];
        }
        if ($room->reuid != 0) {
            return ['status' => 0, 'msg' => __('messages.Member.delRoomOne2Many.room_already_reserved')];
        }
        $this->make('redis')->hdel('hroom_duration:' . $room->uid . ':' . $room->roomtid, $room->id);//删除对应的redis
        $room->delete();
        return ['status' => 1, 'msg' => __('messages.Charge.del.success')];
    }

    /*
     * 删除一对一 by  desmond
     */

    /**
     * 写充值的 日志 TODO 优化到充值服务中去
     *
     * @param string $word
     * @param string $recodeurl
     */
    protected function logResult($word = '', $recodeurl = '')
    {
        if ($recodeurl) {
            $recordLog = $recodeurl;
        } else {
            $recordLog = SiteSer::config('pay_log_file');
        }
        $fp = fopen($recordLog, "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . date("Ymd H:i:s", time()) . "\n" . $word . "\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    // 取得房间暱称、標籤
    public function getInfo($rid)
    {
        $key = static::ROOM_KEY.$rid;
        $redis = $this->make('redis');

        // 目前仅读 redis，不读 DB
        list($roomInfo, $feature, $content) = $redis->hmget($key, ['room_info', 'feature', 'content']);

        return [
            'room_info' => $roomInfo ? $roomInfo : '',
            'feature'   => $feature ? $feature : '',
            'content'   => $content ? $content : '',
        ];
    }

    // 设定房间暱称、標籤
    public function setInfo($rid, $roomInfo, $feature = '', $content = '')
    {
        $redis = $this->make('redis');

        if (mb_strlen($roomInfo) > 10) {
            return false;
        }

        // Redis
        $key = static::ROOM_KEY.$rid;
        $redis->hmset($key, ['room_info' => $roomInfo, 'feature' => $feature, 'content' => $content]);

        // DB
        $anchorExt = AnchorExt::find($rid);
        if (!$anchorExt) {
            $anchorExt = new AnchorExt([
                'uid' => $rid,
            ]);
        }
        $anchorExt->room_info = $roomInfo;
        $anchorExt->save();

        $this->make(UserHostRepository::class)->updateOrCreate($rid, ['feature' => $feature, 'content' => $content]);

        return true;
    }
}
