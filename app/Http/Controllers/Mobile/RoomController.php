<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2017/2/9
 * Time: 11:10
 */

namespace App\Http\Controllers\Mobile;

use Illuminate\Support\Facades\Session;
use App\Facades\Site;
use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Http\Controllers\Controller;
use App\Libraries\SuccessResponse;
use App\Models\LiveList;
use App\Models\MallList;
use App\Models\RoomDuration;
use App\Models\RoomOneToMore;
use App\Models\RoomStatus;
use App\Models\UserBuyOneToMore;
use App\Models\Users;
use App\Services\Room\NoSocketChannelException;
use App\Services\Room\RoomService;
use App\Services\Room\SocketService;
use App\Services\Safe\SafeService;
use App\Services\User\UserService;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Mews\Captcha\Facades\Captcha;
use Illuminate\Pagination\LengthAwarePaginator;

class RoomController extends Controller
{
    const FLAG_ONE_TO_ONE = 0b01;
    const FLAG_ONE_TO_MANY = 0b10;
    static $ORIGINS = [
        11,//网页房间内
        12,//网页房间外
        13,//网页后台
        21,//安卓房间内
        22,//安卓房间外
        31,//IOS房间内
        32,//IOS房间外
    ];


    public function listReservation($type = 0b11)
    {
        $lists = [];
        $flashVersion = SiteSer::config('flash_version') ?: 'v201504092044';
        if ($type & static::FLAG_ONE_TO_ONE) {//一对一
            $lists['oneToOne'] = $this->getReserveOneToOneByUid(Auth::id(), $flashVersion);
        }
        if ($type & static::FLAG_ONE_TO_MANY) {//一对多
            $lists['oneToMany'] = $this->getReserveOneToManyByUid(Auth::id(), $flashVersion);
        }
        return JsonResponse::create(['status' => 1, 'data' => $lists]);
    }

    /**
     * @param $uid
     * @param $flashVersion
     * @return array|\Illuminate\Support\Collection
     */
    public function getReserveOneToOneByUid($uid, $flashVersion)
    {
        $list = collect();
        $myReservation = RoomDuration::where('reuid', $uid)
            ->whereRaw('starttime>(now()-duration)')
            ->orderBy('starttime', 'desc')
            ->get();
        if (!$myReservation->count()) return $list;
        // 从redis 获取一对一预约数据
        if(isset(resolve('one2one')->getHomeBookList($flashVersion)['rooms'])){
            $rooms = resolve('one2one')->getHomeBookList($flashVersion)['rooms'] ?? [];
        }

        foreach ($rooms as $room) {
            if ($myReservation->where('uid', $room['rid'])->where('id', $room['id'])->first()) {
                $room['listType'] = 'myres';
                $list[] = $room;
            }
        }
        return $list;
    }

    /**
     * @param $uid
     * @param $flashVersion
     * @return array|\Illuminate\Support\Collection
     */
    public function getReserveOneToManyByUid($uid, $flashVersion)
    {
        $list = collect();

        $oneToMore = UserBuyOneToMore::where('uid', $uid)->whereRaw('starttime>DATE_SUB(now(),INTERVAL duration  SECOND)')->orderBy('starttime', 'desc')->get();
        if (!$oneToMore->count()) return $list;
        // 从redis 获取一对多预约数据
        $rooms = resolve('one2more')->getHomeBookList($flashVersion)['rooms'] ?? [];
        foreach ($rooms as $room) {
            if ($oneToMore->where('rid', $room['uid'])->where('onetomore', $room['id'])->first()) {
                $room['listType'] = 'myticket';
                $list[] = $room;
            }
        }
        return $list;
    }

    /**
     * 检查密码房密码
     * @return JsonResponse
     */
    public function checkPwd()
    {
        $password = $this->request()->get('password');
        $rid = $this->request()->get('rid');
        $type = $this->getAnchorRoomType($rid);
        if ($type != 2) return new JsonResponse(['status' => 0, 'msg' => '密码房异常,请联系运营重新开启一下密码房间的开关']);
        if (empty($rid)) return new JsonResponse(['status' => 0, 'msg' => '房间号错误!']);
        if (empty($password)) {
            return $this->geterrorsAction();
        }
        $heads = $this->request()->headers;
        //房间进入密码，超过五次就要输入验证码，这个五次是通过phpsessionid来判断的
        $roomstatus = $this->getRoomStatus($rid, 2);
        $authorization = explode(' ',$heads->get('Authorization'));
        $jwt = explode('.',$authorization[1]);

        $keys_room = 'keys_room_passwd:' . $jwt[0] . ':' . $rid;
        $times = $this->make('redis')->get($keys_room) ?: 0;
        if ($times >= 5) {
            $captcha = $this->request()->get('captcha');
            if (empty($captcha)) {
                return new JsonResponse(['status' => 0, 'msg' => '请输入验证码!', 'data' => ['times' => $times, 'cmd' => 'showCaptcha']]);
            }
            if (!Captcha::check($captcha)) return new JsonResponse(['status' => 0, 'msg' => '验证码错误!', 'data' => ['times' => $times]]);;
        }
        if (strlen($password) < 6 || strlen($password) > 22 || !preg_match('/^\w{6,22}$/', $password)) {
            $this->make('redis')->setex($keys_room, 3600, $times + 1);
            return new JsonResponse([
                'status' => 0,
                'msg' => "密码格式错误!",
                'data' => ['times' => $times + 1],
            ]);
        }
        if ($password != $roomstatus['pwd']) {
            $this->make('redis')->setex($keys_room, 3600, $times + 1);
            return new JsonResponse([
                'status' => 0,
                'msg' => "密码错误!",
                'data' => ['times' => $times + 1],
            ]);
        }
        $this->make('redis')->hset('keys_room_passwd:' . $rid . ':' . $jwt[0], 'status', 1);
        return new JsonResponse(['status' => 1, 'msg' => '验证成功']);
    }

    /**
     *房间密码错误次数请求
     * @author TX
     * updata 2015.4.16
     * @return JsonResponse
     */
    public function geterrorsAction()
    {
        $rid = $this->request()->get('roomid');
        if (empty($rid)) return new JsonResponse(['status' => 0, 'msg' => '房间号错误!']);
//        $this->get('session')->start();
        $session_name = Session::getName();
        if (isset($_POST[$session_name])) {
            Session::setId($_POST[$session_name]);
        }
        $sessionid = Session::getId();
        $keys_room = 'keys_room_errorpasswd:' . $sessionid . ':' . $rid;
        $times = $this->make('redis')->hget($keys_room, 'times');
        if (empty($times)) $times = 0;
        return new JsonResponse(['status' => 1, 'times' => $times]);
    }

    /**
     * @description 获取房间权限
     * @author      TX
     * @date        2015.4.20
     */
    public function getRoomStatus($uid, $tid)
    {
        $hasname = 'hroom_status:' . $uid . ':' . $tid;
        $status = $this->make('redis')->hget($hasname, 'status');
        if (!empty($status)) {
            if ($status == 1) {
                $data = $this->make('redis')->hgetall($hasname);
            } else {
                return null;
            }
        } else {
//            $datas =  $this->getDoctrine()->getRepository('Video\ProjectBundle\Entity\VideoRoomStatus')->createQueryBuilder('r')
//                ->where('r.uid='.$uid.'  and  r.tid='.$tid.' and r.status = 1')
//                ->orderby('r.id','ASC')
//                ->getQuery();
//            $roomdata = $datas->getResult();
            $data = RoomStatus::where('uid', $uid)
                ->where('tid', $tid)->where('status', 1)
                ->orderBy('id', 'ASC')->first();
            /**
             * dc修改，有数据时再转换数组
             */
            $data = $data ? $data->toArray() : $data;
            /*
             * 因上面$roomdata被注释,改用eloquent查询方式.忘记注释判断,导致房间获取失败
             * 现添加注释
             * @author dc
             * @version 20160407
             * */
            if (empty($roomdata)) {
                return null;
            }

            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $this->make('redis')->hset('hroom_status:' . $uid . ':' . $tid, $key, $value);
                }
            }
        }

        return $data;
    }

    /**
     * @return static
     */
    public function getConf()
    {
        $conf = [
            'img_host' => SiteSer::config('img_host'),
            'cdn_host' => SiteSer::config('cdn_host'),
            'flash_version' => SiteSer::config('flash_version'),
            'publish_version' => SiteSer::config('publish_version'), //young添加
            'in_limit_points' => Redis::hget('hconf', 'in_limit_points') ?: 0,
            'in_limit_safemail' => Redis::hget('hconf', 'in_limit_safemail') ?: 0,   //1开，0关
        ];
        return JsonResponse::create(['data' => $conf]);
    }

    /**
     * @param $rid
     * @return static
     */
    public function getRoom($rid)
    {

        try {
            $roomService = resolve('roomService');
            $room = $roomService->getRoom($rid, Auth::id());
            $tid = $roomService->getCurrentTimeRoomStatus();

            $user = UserSer::getUserByUid(Auth::id());
            $roomInfo = [
                'room_name'=>$room['user']['nickname'],
                'header_pic'=>$room['user']['headimg'],
                'room_pic'=>$room['user']['cover'],
                'live_status'=>$room['status'],
                'live_device_type'=>$room['origin'],
                'tid'=>$tid ?: 1,
                'is_password'=>$roomService->getPasswordRoom()?1:0,
            ];

            $roomExtend = [
                'start_time'=> null,
                'end_time'=> null,
                'user_num'=> $room['total'],
                'room_price'=> 0,
                'time_length'=> 0,
                'room_id'=> $rid,
                'class_id'=> 0,
            ];
            switch ($tid){
                case 8 :
                    $one2more = resolve('one2more')->getRunningData();
                    $roomExtend['start_time'] = $one2more['starttime'];
                    $roomExtend['end_time'] = $one2more['endtime'];
                    $roomExtend['user_num'] = $one2more['nums'];
                    $roomExtend['room_price'] = $one2more['points'];
                    $roomExtend['class_id'] = $one2more['onetomore'];
                    break;
                case 4:
                    $one2one = resolve('one2one')->getRunningData();
                    $roomExtend['start_time'] = $one2one['starttime'];
                    $roomExtend['end_time'] = date('Y-m-d H:i:s',strtotime($one2one['starttime']) + $one2one['duration']);
                    $roomExtend['user_num'] = $one2one['tickets']?:($one2one['reuid'] ?1:0);
                    $roomExtend['room_price'] = $one2one['points'];
                    $roomExtend['class_id'] = $one2one['id'];
                    break;
                case 6 :
                    $durRoom = $roomService->getDurationRoom();
                    $roomExtend['start_time'] = null;
                    $roomExtend['end_time'] = null;
                    $roomExtend['user_num'] = $room['total'];
                    $roomExtend['room_price'] = $durRoom['timecost'];
                    break;
                default:;
            }
            $roomExtend['time_length'] = strtotime($roomExtend['end_time'])-strtotime($roomExtend['start_time']);

            $room_user = [
                'authority_in'=>1
            ];

            if(in_array($tid,[8,4,6]) && Auth::guest() ){   //游客进特殊房间
                $room_user['authority_in'] = 309;
            }else{
                    switch ($tid){
                        case 8:
                            if(!$roomService->whiteList())   $room_user['authority_in'] = 302;
                            break;
                        case 4:
                            if(!$roomService->checkCanIn()){
                                    $room_user['authority_in'] =  $roomExtend['user_num'] ? 304 : 303;
                            }
                            break;
                        case 6:
                            if($user['points'] < $roomExtend['room_price'])   $room_user['authority_in'] = 305;
                            break;
                    }
                   if($room_user['authority_in']==1 && $roomService->getPasswordRoom()){
                       $room_user['authority_in'] = 306;
                   }
            }


           $socket = [];
            /** @var SocketService $socketService */
            $socketService = resolve(SocketService::class);
            $chatServer = [];
            $msg = "";
            $chatServer = $socketService->getNextServerAvailable();
            $socket['host'] =  $chatServer['host'];
            $socket['ip'] =  $chatServer['ip'];
            $socket['port'] =  $chatServer['port'];

        } catch (NoSocketChannelException $e) {
            $msg = $e->getMessage();
            $socket['host'] =  "";
            $socket['ip'] =  "";
            $socket['port'] =  "";
            $roomInfo = [];
            $roomExtend = [];
            $room_user = [];
            Log::info("手机直播间异常：".$msg);
        }
        return JsonResponse::create(['data' => array_merge($roomInfo,$roomExtend,$room_user,$socket),'msg'=>$msg]);
    }
    /**
     * @param $rid
     * @return static
     */
    public function _getRoom($rid)
    {
        resolve('request')->attributes->set('rid', $rid);
        $request = resolve('request');
        $conf = $this->getRoomConf($request)->getData(true) ?? [];
        $access = $this->getRoomAccess($rid)->getData(true) ?? [];
        return JsonResponse::create(['data' => (object)(array_merge($conf, $access))]);
    }

    /**
     */
    public function getRoomConf(Request $request)
    {
        $rid = $request->get('rid');
        $redis = resolve('redis');
        $roomService = resolve(RoomService::class);

        $room = $roomService->getRoom($rid, Auth::id());
        /** @var SocketService $socketService */
        $socketService = resolve(SocketService::class);
        if (empty($room)) {
            //创建房间
            if ($this->isHost($rid)) {
                $room = $roomService->addRoom($rid, $rid, Auth::id());
            } else {
                return JsonResponse::create(['status' => 0, 'msg' => '房间不存在']);
            }
        }
        try {
            $chatServer = $socketService->getNextServerAvailable();
        } catch (NoSocketChannelException $e) {
            return JsonResponse::create(['status' => 0, 'msg' => $e->getMessage()]);
        }
        $data = [
            'rid' => $rid,
            'chatServer' => $chatServer,
            'in_limit_points' => $redis->hget('hconf', 'in_limit_points') ?: 0,
            'in_limit_safemail' => $redis->hget('hconf', 'in_limit_safemail') ?: 0,   //1开，0关
            'certificate' => resolve(SafeService::class)->getLcertificate(),
        ];
        return JsonResponse::create(['msg'=>'获取成功','status'=>1,'data'=>$data]);
    }

    protected function isHost($rid)
    {
        return Auth::id() == $rid;
    }

    public function getRoomAccess($rid)
    {
        $return = [];
        $redis = resolve('redis');
        $now = time();
        $one2more = resolve('one2more');

        /** 判断房间一对多 */
        if ($room = $one2more->getRunningData()) {
            $return['onetomore'] = $room;
            $return['onetomore']['access'] = 0;

            if ($one2more->checkUserBuyRunning(Auth::id())) {
                /** 判断用户是否购买 */
                $return['onetomore']['access'] = 1;
            }
        }

        /** 一对一 */
        $one2one = resolve('one2one');
        if ($room = $one2one->getRunningData()) {
            $return['ord'] = $room;
            $return['ord']['access'] = Auth::id() == $room['reuid'] ? 1 : 0;
        }
        /** 时长房 */
        if ($redis->exists("hroom_status:" . $rid . ":6") && $redis->hget("hroom_status:" . $rid . ":6", "status") == '1') {
            if ($redis->hget("htimecost:" . $rid, "timecost_status")) {
                $timecost = $redis->hget("hroom_status:" . $rid . ":6", "timecost") ?: 0;
                $discount = $redis->hget('hgroups:special:' . $this->userInfo['vip'], 'discount') ?: 10;
                $return['timecost'] = [
                    'price' => $timecost,
                    'access' => 1,
                    'discount' => $discount,
                    'discountValue' => ceil($timecost * $discount / 10),
                ];
            }
        }
        return JsonResponse::create($return);
    }

//    public function getRoomAccess($rid)
//    {
//        $return = [];
//        $redis = $this->make('redis');
//        $now = time();
//        $roomSer = resolve('roomService')->getRoom($rid,$this->userInfo['uid']);
//
//        /** 判断房间一对多 */
//        if ($roomSer->checkOne2More()) {
//            $room =  $roomSer->getExtendRoom();
//            if($room){
//                $return['onetomore'] = $room;
//                $return['onetomore']['id'] = $room['id'];
//                $return['onetomore']['access'] = 0;
//                if (Auth::id()) {
//                    /** 判断用户是否购买 */
//                    $uids = array_merge(explode(',', $room['uids']), explode(',', isset($room['tickets']) ? $room['tickets'] : ''));
//                    $uids = array_filter($uids);
//                    if (in_array(Auth::id(), $uids)) {
//                        $return['onetomore']['access'] = 1;
//                    }
//                }
//            }
//        }
//
//        /** 一对一 */
//        if ($roomSer->checkOne2One()) {
//            $room =  $roomSer->getExtendRoom();
//            if($room){
//                $return['ord'] = $room;
//                $return['ord']['access'] = Auth::id() == $room['reuid'] ? 1 : 0;
//            }
//        }
//        /** 时长房 */
//        if ($roomSer->checkTimecost()) {
//            $room =  $roomSer->getExtendRoom();
//            $discount = $redis->hget('hgroups:special:' . $this->userInfo['vip'], 'discount') ?: 10;
//            $timecost = $room['timecost'] ?? 0;
//            $return['timecost'] = [
//                'price' => $timecost,
//                'discount' => $discount,
//                'discountValue' => ceil($timecost * $discount / 10)
//            ];
//        }
//        return JsonResponse::create($return);
//    }


    /*
    *  一对多房间记录接口by desmond
    */
    public function listOneToMoreByHost(Request $request)
    {

        $start_date = $request->get('starttime') ? $request->get('starttime') . ' 00:00:00' : date('Y-m-d H:i:s');
        $end_date = $request->get('endtime') ? $request->get('endtime') . ' 23:59:59' : date('Y-m-d 23:59:59');

        $result['data'] = RoomOneToMore::where('uid', Auth::id())
            ->where('status', 0)
            ->whereBetween('starttime', [$start_date, $end_date])
            ->orderBy('starttime', 'DESC')
            ->paginate();

        return JsonResponse::create($result);
    }

    /*
     * 判断登录的主播是否开通一对多
     */
    public function competence()
    {
        $uid = $this->request()->input('uid') ?: '';
        $key = 'hroom_status:' . $uid . ':7';
        $keys = 'hroom_status:' . $uid . ':4';
        $listonetomany = $this->make('redis')->hGetAll($key);
        $listonetoone = $this->make('redis')->hGetAll($keys);
        $result[0] = (object)$listonetomany;
        $result[1] = (object)$listonetoone;
        if ($listonetoone && $listonetoone) {
            return JsonResponse::create(['status' => 1, 'data' => $result]);
        } else {
            return JsonResponse::create(['status' => 0, 'data' => '']);
        }

    }

    /*
    *  直播记录接口 by desmond
    */
    public function showlist()
    {

        $start_time = $this->request()->input('starttime') ? strtotime($this->request()->input('starttime') . ' 00:00:00') : strtotime('-1 month');
        $end_time = $this->request()->input('endtime') ? strtotime($this->request()->input('endtime') . ' 23:59:59') : strtotime('tomorrow') - 1;
        $uid = Auth::id();

        $start_init  =   date("Y-m-d 00:00:00", strtotime($this->request()->input('starttime')));
        $end_init  =   date("Y-m-d 23:59:59", strtotime($this->request()->input('endtime')));


        $result = LiveList::where('uid', '=', $uid)
            ->where('start_time', '>=', date("Y-m-d H:i:s", $start_time-3600*24))
            ->where('start_time', '<=', date("Y-m-d H:i:s", $end_time))
            ->where('duration', '<>', 0)
            ->select('id', 'created', 'start_time', 'rid', 'duration')
            ->orderBy('id', 'desc')
            ->get();


        $liveinfo = [];
        $duration_total = 0;
        foreach ($result as $key => $value) {
            //如果开始时间是在前一天的
            $starttime = date("Y-m-d 23:59:59", strtotime($value['start_time']));
            $endtime = date("Y-m-d H:i:s", strtotime($value['start_time']) + $value['duration']);
            //var_dump($starttime.'==='.$endtime.'<br>');
            if($endtime>$start_init) {
                $temp = [];
                $temp['id'] = $value['id'];
                if ($endtime > $end_init && $value['start_time'] > $start_init ) {
                    $temp['start_time'] = $value['start_time'];
                    $temp['end_time'] = date("Y-m-d 00:00:00", strtotime($value['start_time']) + 3600 * 24);
                    $temp['duration'] = $value['duration'] = strtotime($temp['end_time']) - strtotime($value['start_time']);
                } elseif ($endtime > $start_init && $value['start_time'] < $start_init) {
                    $temp['start_time'] = $start_init;
                    $temp['end_time'] = $endtime;
                    $temp['duration'] = strtotime($endtime) - $start_time;
                } else {
                    $temp['start_time'] = $value['start_time'];
                    $temp['end_time'] = date("Y-m-d H:i:s", strtotime($value['start_time']) + $value['duration']);
                    $temp['duration'] = $value['duration'];

                }
                $duration_total = $duration_total + $temp['duration'];
                array_push($liveinfo,$temp);
            }
        }
        $getinfo['list'] = $liveinfo;
        $getinfo['duration_total'] = $duration_total;
        return SuccessResponse::create($getinfo, $msg = '获取成功', $status = 1);


    }

    public function roomSetDuration(Request $request)
    {
        $data = [];
        $data = $request->only(['mintime', 'hour', 'minute', 'tid', 'duration', 'points','origin']);

        if(empty($data['origin'])){
            $data['origin']=21;
        }
        if ($data['points'] < 2000) {
            return new JsonResponse(['status' => 0, 'msg' => '手动设置的钻石数必须大于2000钻石']);
        }
        $roomservice = resolve(RoomService::class);
        $result = $roomservice->addOnetoOne($data);
        return new JsonResponse($result);
    }


    /*
       *  一对一房间记录接口
       */
    public function listOneToOneByHost()
    {

        //$this->isLogin() or die;

        $start_date = $this->request()->input('starttime') ? $this->request()->input('starttime') . ' 00:00:00' : date('0000-00-00 00:00:00');
        $end_date = $this->request()->input('endtime') ? $this->request()->input('endtime') . ' 23:59:59' : date('Y-m-d 23:59:59');
        $uid = Auth::id();

        $page = $this->request()->get('page');
        LengthAwarePaginator::currentPageResolver(function () use ($page) {
            return $page ?: 1;
        });
        $result = RoomDuration::where('uid', '=', $uid)
            ->where('status', '=', 0)
            ->where('starttime', '>=', $start_date)
            ->where('starttime', '<=', $end_date)
            ->orderBy('starttime', '=', 'DESC')
            ->paginate(15)
            ->toArray();


        return JsonResponse::create(['status' => 1, 'data' => $result,'msg'=>'获取成功']);


    }

}