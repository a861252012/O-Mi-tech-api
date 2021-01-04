<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2017/2/9
 * Time: 11:10
 * @apiDefine Room 直播間
 */

namespace App\Http\Controllers\Mobile;

use App\Services\GuardianService;
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

    protected $guardianService;

    public function __construct(Request $request, GuardianService $guardianService)
    {
        parent::__construct($request);
        $this->guardianService = $guardianService;
    }

    public function listReservation($type = 0b11)
    {
        $lists = [];
        $flashVersion = SiteSer::config('publish_version') ?: 'v201504092044';
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
        // 获取我的预约的房间
        $myres = [];
        if ($uid) {
            $myReservation = RoomDuration::with('anchor')->where('reuid', $uid)
                ->where('endtime', '>', date('Y-m-d H:i:s', time()))
                ->orderBy('starttime', 'desc')
                ->get();
            if (!empty($myReservation)) {
                foreach ($myReservation as $item) {
                    $roomInfo = Redis::hgetall('hvediosKtv:' . $item->uid);
                    $room['id'] = $item->id;
                    $room['rid'] = $item->uid;
                    $room['tid'] = 4;
                    $room['nickname'] = $item->anchor->nickname;
                    $room['cover'] = $item->anchor->cover;
                    $room['start_time'] = $item->starttime;
                    $room['end_time'] = $item->endtime;
                    $room['duration'] = $item->duration;
                    $room['one_to_one_status'] = 1;
                    $room['origin'] = $item->origin;
                    $room['new_user'] = intval(Redis::hget('huser_icon:' . $item->uid, 'new_user'));
                    $room['live_status'] = isset($roomInfo['status']) ? intval($roomInfo['status']) : 0;
                    $room['top'] = isset($roomInfo['top']) ? intval($roomInfo['top']) : 0;
                    $myres[] = $room;
                }
            }
        }
        return $myres;
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
        if ($type != 2) return new JsonResponse(['status' => 0, 'msg' => __('messages.MobileRoom.checkPwd.unknown_error')]);
        if (empty($rid)) return new JsonResponse(['status' => 0, 'msg' => __('messages.MobileRoom.checkPwd.room_id_is_wrong')]);
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
                return new JsonResponse(['status' => 0, 'msg' => __('messages.MobileRoom.checkPwd.captcha_required'), 'data' => ['times' => $times, 'cmd' => 'showCaptcha']]);
            }
            if (!Captcha::check($captcha)) return new JsonResponse(['status' => 0, 'msg' => __('messages.MobileRoom.checkPwd.captcha_error'), 'data' => ['times' => $times]]);;
        }
        if (strlen($password) < 6 || strlen($password) > 22 || !preg_match('/^\w{6,22}$/', $password)) {
            $this->make('redis')->setex($keys_room, 3600, $times + 1);
            return new JsonResponse([
                'status' => 0,
                'msg' => __('messages.MobileRoom.checkPwd.password_format_wrong'),
                'data' => ['times' => $times + 1],
            ]);
        }
        if ($password != $roomstatus['pwd']) {
            $this->make('redis')->setex($keys_room, 3600, $times + 1);
            return new JsonResponse([
                'status' => 0,
                'msg' => __('messages.MobileRoom.checkPwd.password_is_wrong'),
                'data' => ['times' => $times + 1],
            ]);
        }
        $this->make('redis')->hset('keys_room_passwd:' . $rid . ':' . $jwt[0], 'status', 1);
        return new JsonResponse(['status' => 1, 'msg' => __('messages.MobileRoom.checkPwd.validation_success')]);
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
        if (empty($rid)) return new JsonResponse(['status' => 0, 'msg' => __('messages.MobileRoom.geterrorsAction.room_id_wrong')]);
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
     * @api {get} /get_room/{rid} 進入直播間
     * @apiGroup Room
     * @apiName get_room
     * @apiVersion 1.0.0
     *
     * @apiParam {Int} rid 房間id
     *
     * @apiError (Error Status) 1
     *
     * @apiSuccess {String} room_name 主播 user.nickname
     * @apiSuccess {String} header_pic 主播大頭照 user.headimg.jpg
     * @apiSuccess {String} room_pic 主播海報 user.cover
     * @apiSuccess {String} live_status 是否在線 (hvediosKtv:{rid} status)
     * @apiSuccess {String} live_device_type 裝置類型 (hvediosKtv:{rid} origin)
     * @apiSuccess {String} tid 当前房间时间点状态，有优化级，順序如下<br>
     *                      <code>4</code>: 一对一<br>
     *                      <code>8</code>: 一对多<br>
     *                      <code>6</code>: 时长房(限制房间)、已廢除<br>
     *                      <code>1</code>: 普通房
     * @apiSuccess {String} is_password 是否為密碼房 (0, 1)
     * @apiSuccess {String} start_time 「一对一」或「一对多」開始時間，沒有則回傳 <code>null</code>
     * @apiSuccess {String} end_time 「一对一」或「一对多」結束時間，沒有則回傳 <code>null</code>
     * @apiSuccess {String} user_num 普通房時顯示房間總人數 (hvediosKtv:{rid} total) ，但這個值不準確，目前也沒用途<br>
     *                      <hr>
     *                      一對多後改成「已購票人數」
     *                      <hr>
     *                      一對一後改成抓 hroom_duration:{rid}:4 的值，邏輯如下：<br>
     *                      <code>key.tickets ?: key.reuid ? 1 : 0</code>
     * @apiSuccess {String} room_price 房間價格，無值時回傳數字 <code>0</code>，有值時回傳字串！
     * @apiSuccess {String} room_price_sale 守護身份優惠價
     * @apiSuccess {String} room_info 房間描述
     * @apiSuccess {String} time_length 開始時間到結束時間的秒數
     * @apiSuccess {String} room_id rid
     * @apiSuccess {String} class_id 一對多是場次編號<br>
     *                      一對一是抓 hroom_duration:{rid}:4 的 id，內容不詳
     * @apiSuccess {String} authority_in 是否有权限进入房间<br>
     *                      <code>1</code>: 是<br>
     *                      <code>302</code>: 尚未购买一对多<br>
     *                      <code>303</code>: 尚未预约一对一<br>
     *                      <code>304</code>: 一对一已被其他人预约<br>
     *                      <code>305</code>: 余额不足，不能进入时长房间<br>
     *                      <code>306</code>: 密码房间，请输入密码<br>
     *                      <code>307</code>: 用户不合法<br>
     *                      <code>308</code>: 房间状态异常<br>
     *                      <code>309</code>: 游客进特殊房间<br>
     * @apiSuccess {String} host socket 連線資訊，挑出在線人數最少的 channel
     * @apiSuccess {String} ip socket 連線資訊，挑出在線人數最少的 channel
     * @apiSuccess {String} port socket 連線資訊，挑出在線人數最少的 channel
     *
     * @apiSuccessExample {json} 成功回應
     * {
     *   "data": {
     *     "room_name": "ted",
     *     "header_pic": "a8193c14a4d0568096a920825defba39.jpg",
     *     "room_pic": "9494029_1596703403.jpeg",
     *     "live_status": "0",
     *     "live_device_type": "11",
     *     "tid": 1,
     *     "is_password": 0,
     *     "start_time": "2020-08-13 17:10:00",
     *     "end_time": "2020-08-13 17:40:00",
     *     "user_num": "0",
     *     "room_price": "399",
     *     "room_price_sale": 0,
     *     "room_info": "",
     *     "time_length": 0,
     *     "room_id": "9493275",
     *     "class_id": 0,
     *     "authority_in": 1,
     *     "host": "10.2.121.15,10.2.121.240",
     *     "ip": "10.2.121.100",
     *     "port": "1057"
     *   },
     *   "msg": "",
     *   "status": 1
     * }
     */
    public function getRoom($rid)
    {
        $uid = Auth::id();
        $roomService = resolve('roomService');
        $room = $roomService->getRoom($rid, $uid);
        $tid = $roomService->getCurrentTimeRoomStatus();
        $user = UserSer::getUserByUid($uid);

        $roomInfo = [
            'room_name'=>$room['user']['nickname'],
            'header_pic'=>$room['user']['headimg'],
            'room_pic'=>$room['user']['cover'],
            'live_status'=>$room['status'],
            'live_device_type'=>$room['origin']??-1,
            'tid'=>$tid ?: 1,
            'is_password'=>$roomService->getPasswordRoom()?1:0,
        ];

        /* 取得房間資訊 */
        $ancList = collect(UserSer::anchorlist())->firstWhere('rid', $rid);

        $roomExtend = [
            'start_time'=> null,
            'end_time'=> null,
            'user_num'=> $room['total'],
            'room_price'=> 0,
            'room_price_sale' => 0,
            'room_info' => $ancList['room_info'] ?? '',
            'time_length'=> 0,
            'room_id'=> $rid,
            'class_id'=> 0,
        ];

        switch ($tid) {
            case 8:
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
                $roomExtend['end_time'] = date('Y-m-d H:i:s', strtotime($one2one['starttime']) + $one2one['duration']);
                $roomExtend['user_num'] = $one2one['tickets'] ?: ($one2one['reuid'] ? 1 : 0);
                $roomExtend['room_price'] = $one2one['points'];
                $roomExtend['class_id'] = $one2one['id'];
                break;
            case 6:
                $durRoom = $roomService->getDurationRoom();
                $roomExtend['start_time'] = null;
                $roomExtend['end_time'] = null;
                $roomExtend['user_num'] = $room['total'];
                $roomExtend['room_price'] = $durRoom['timecost'];
                break;
        }

        /* 判斷是否守護身份與計算守護優惠價 */
        if (!empty($user->guard_id) && time() < strtotime($user->guard_end)) {
            $roomExtend['room_price_sale'] = $this->guardianService->calculRoomSale(
                $roomExtend['room_price'],
                $user->guardianInfo->show_discount
            );
        }

        $roomExtend['time_length'] = strtotime($roomExtend['end_time'])-strtotime($roomExtend['start_time']);

        $room_user = [
            'authority_in'=>1
        ];

        if ((in_array($tid, [8,4,6]) || $roomService->getPasswordRoom()) && Auth::guest()) { //游客进特殊房间
            $room_user['authority_in'] = 309;
        } else {
            switch ($tid) {
                case 8:
                    if (!$roomService->whiteList()) {
                        $room_user['authority_in'] = 302;
                    }
                    break;

                case 4:
                    if (!$roomService->checkCanIn()) {
                        $room_user['authority_in'] =  $roomExtend['user_num'] ? 304 : 303;
                    }
                    break;

                case 6:
                    if ($user['points'] < $roomExtend['room_price']
                        || ($user['points'] < $roomExtend['room_price_sale'] && $roomExtend['room_price_sale'] != 0)
                    ) {
                        $room_user['authority_in'] = 305;
                    }
                    break;
            }

            if ($room_user['authority_in']==1 && $roomService->getPasswordRoom()) {
                $room_user['authority_in'] = 306;
            }
        }

        $socket = [];
        $socketService = resolve(SocketService::class);
        $chatServer = [];
        $msg = "";

        try {
            $chatServer = $socketService->getNextServerAvailable($this->isHost($rid));
        } catch (NoSocketChannelException $e) {
            $msg = $e->getMessage();
            Log::info("手机直播间异常：". $msg);

            $chatServer = [
                'host' => '',
                'ip' => '',
                'port' => '',
            ];
            $roomInfo = [];
            $roomExtend = [];
            $room_user = [];
        }
        $socket['host'] =  $chatServer['host'];
        $socket['ip'] =  $chatServer['ip'];
        $socket['port'] =  $chatServer['port'];
        $data = array_merge($roomInfo, $roomExtend, $room_user, $socket);

        return JsonResponse::create(['data' => $data, 'msg' => $msg]);
    }

    /*
     * 手机端一对一接口
     */
    public function getRoomonetoone($rid)
    {

        try {
            $roomService = resolve('roomService');
            $room = $roomService->getRoom($rid, Auth::id());
            $tid = $roomService->getCurrentMobileOnetoone();
            $user = UserSer::getUserByUid(Auth::id());
            $roomInfo = [
                'room_name'=>$room['user']['nickname'],
                'header_pic'=>$room['user']['headimg'],
                'room_pic'=>$room['user']['cover'],
                'live_status'=>0,     // 改为未开播
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
                case 4:
                    $one2one = resolve('one2one')->getRunningOnetooneDatas();

                    $roomExtend['start_time'] = $one2one['starttime'];
                    $roomExtend['end_time'] = date('Y-m-d H:i:s',strtotime($one2one['starttime']) + $one2one['duration']);
                    $roomExtend['user_num'] = $one2one['tickets']?:($one2one['reuid'] ?1:0);
                    $roomExtend['room_price'] = $one2one['points'];
                    $roomExtend['class_id'] = $one2one['id'];
                    break;

                default:;
            }
            $roomExtend['time_length'] = strtotime($roomExtend['end_time'])-strtotime($roomExtend['start_time']);

            $room_user = [
                'authority_in'=>1
            ];

            if(in_array($tid,[4,]) && Auth::guest() ){   //游客进特殊房间
                $room_user['authority_in'] = 309;
            }else{
                switch ($tid){

                    case 4:
                        if(!$roomService->checkCanIn()){

                            $room_user['authority_in'] =  $roomExtend['user_num'] ? 304 : 303;
                        }
                        break;

                }

            }
            $socket = [];
            /** @var SocketService $socketService */
            $socketService = resolve(SocketService::class);
            $chatServer = [];
            $msg = "";
            $chatServer = $socketService->getNextServerAvailable($this->isHost($rid));
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
                return JsonResponse::create(['status' => 0, 'msg' => __('messages.MobileRoom.getRoomConf.room_is_not_exist')]);
            }
        }
        try {
            $chatServer = $socketService->getNextServerAvailable($this->isHost($rid));
        } catch (NoSocketChannelException $e) {
            return JsonResponse::create(['status' => 0, 'msg' => $e->getMessage()]);
        }
        $data = [
            'rid' => $rid,
            'chatServer' => $chatServer,
            'in_limit_points' => $redis->hget('hsite_config'.SiteSer::siteId(), 'in_limit_points') ?: 0,
            'in_limit_safemail' => $redis->hget('hsite_config'.SiteSer::siteId(), 'in_limit_safemail') ?: 0,   //1开，0关
            'certificate' => resolve(SafeService::class)->getLcertificate(),
        ];
        return JsonResponse::create(['msg'=>__('messages.success'),'status'=>1,'data'=>$data]);
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
        return SuccessResponse::create($getinfo, $msg = __('messages.success'), $status = 1);
    }

    public function roomSetDuration(Request $request)
    {
        $data = [];
        $data = $request->only(['mintime', 'hour', 'minute', 'tid', 'duration', 'points','origin']);

        if(empty($data['origin'])){
            $data['origin']=21;
        }
        if ($data['points'] < 2000) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.MobileRoom.roomSetDuration.more_than_2000_points')]);
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


        return JsonResponse::create(['status' => 1, 'data' => $result,'msg'=>__('messages.success')]);
    }
}
