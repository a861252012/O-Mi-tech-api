<?php


namespace App\Http\Controllers;


use App\Facades\SiteSer;
use App\Models\RoomDuration;
use App\Models\RoomOneToMore;
use App\Models\Users;
use App\Services\Room\NoSocketChannelException;
use App\Services\Room\One2MoreRoomService;
use App\Services\Room\One2OneRoomService;
use App\Services\Room\RoomService;
use App\Services\Room\SocketService;
use App\Services\Safe\SafeService;
use App\Services\Safe\RtmpService;
use App\Services\User\UserService;
use Exception;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

class RoomController extends Controller
{
    /**
     * 直播间首页
     * todo 追加打错日志
     * @param $rid
     * @return RoomController|JsonResponse
     */
    public function index($rid, $h5 = false)
    {
        if (SiteSer::config('open_safe')) {
            if (!resolve(SafeService::class)->auth(Auth::id())) {
                JsonResponse::create(['status' => 0]);
            }
        }

        $user = resolve(UserService::class)->getUserByUid($rid);
        if (!$user || !$user->isHost()) return JsonResponse::create(['status' => 0, 'msg' => '房间不存在']);
        $logger = Log::channel('room');
        //get certificate
        $certificate = resolve(SafeService::class)->getLcertificate('socket');

        $roomService = resolve(RoomService::class);
        //$xo_httphost = $roomService->getXOHost();
        //check kickout
        $timeleft = $roomService->checkKickOut($rid, Auth::id());
        if ($timeleft !== true) {
            return JsonResponse::create(['status' => 0, 'msg' => '您被踢出房间，请等待' . ceil($timeleft / 60) . '分钟后重试']);
        }
        $room = $roomService->getRoom($rid, Auth::id());
        $socketService = resolve(SocketService::class);
        $user = Auth::user();
        if (empty($room)) {
            //创建房间
            if ($this->isHost($rid)) {
                $room = $roomService->addRoom($rid, $rid, Auth::id());
            } else {
                return JsonResponse::create(['status' => 0, 'msg' => '房间不存在']);
            }
        }

        try {
            $chatServer = $socketService->getNextServerAvailable($this->isHost($rid));
        } catch (NoSocketChannelException $e) {
            return JsonResponse::create(['status' => 0, 'msg' => $e->getMessage()]);
        }

        $chat_server_addr = $chatServer['host'] . ':' . $chatServer['port'];
        $logger->info('enter_room:' . $rid . ":" . Auth::id() . ':' . $chatServer['id'] . ':' . $chat_server_addr);

        $redis = resolve('redis');
        if (!isset($user['origin'])) $user['origin'] = 12;
        if (!isset($user['created'])) $user['created'] = "";
        $origin = $user['origin'];
        $plat_backurl = [];
        $hplat_info = [];
        $hplat_user = [];

        if (!$this->isHost($rid)) {   //不是主播自己进自己房间
            $tid = $roomService->getCurrentTimeRoomStatus();
            $logger->info('current_tid:' . $tid);
            switch ($tid) {
                case 4:   //一对一房间
//                    $handle = $user ? $pwd_cmd . 'room_one_to_one' : 'login';
                    $handle = $user ? 'room_one_to_one' : 'login';
                    if (!$roomService->checkCanIn()) {
                        $one2one = $roomService->extend_room;
                        $result = $one2one;
                        $result['tickets'] = $one2one['reuid'];
                        $result['rid'] = $one2one['uid'];
                        $result['start_time'] = $one2one['starttime'];
                        $end_time = strtotime($one2one['starttime']) + $one2one['duration'];
                        $result['end_time'] = date('Y-m-d H:i:s', $end_time);
                        $userdata = resolve(UserService::class)->getUserByUid($result['uid']);
                        $result['nickname'] = $userdata['nickname'];
                        $result['username'] = $userdata['username'];
                        $result['handle'] = $handle;
                        unset($result['starttime']);
                        if ($one2one['reuid'] != 0) {

                            return JsonResponse::create(['status' => 0, 'data' => $result, 'msg' => '已被其他用户购买']);
                        }
                        //平台跳转信息
                        $result['plat_url'] = json_encode($plat_backurl, JSON_FORCE_OBJECT);
                        //平台信息
                        $result['hplat_info'] = json_encode($hplat_info, JSON_FORCE_OBJECT);
                        //平台用户信息
                        $result['hplat_user'] = json_encode($hplat_user, JSON_FORCE_OBJECT);
                        // return JsonResponse::create(['status' => 0, 'data' => ['handle' => $handle]]);
                        return JsonResponse::create(['status' => 0, 'data' => $result, 'msg' => '未购买该房间']);
                    }
                    break;
                case 6:   //时长房间
//                    $handle = $user ? $pwd_cmd . 'timecost' : 'login';
                    $handle = $user ? 'timecost' : 'login';
                    if (!$roomService->checkDuration()) {
                        return JsonResponse::create([
                            'status' => 0, 'data' => [
                                'handle' => $handle,
                                'rid' => $rid,
                                'timecost' => $room['room_status'][6]['timecost'],
                                'discount' => $room['discount']['discount'],
                                'discountValue' => $room['discount']['discountValue'],
                                'room' => ['chat_server_addr' => $chat_server_addr],
                                //平台跳转信息
                                'plat_url' => json_encode($plat_backurl, JSON_FORCE_OBJECT),
                                //平台信息
                                'hplat_info' => json_encode($hplat_info, JSON_FORCE_OBJECT),
                                //平台用户信息
                                'hplat_user' => json_encode($hplat_user, JSON_FORCE_OBJECT),
                            ],
                        ]);
                    }
                    break;
//                case 7:   //时长房间和密码房
//                    $handle = $user ? $pwd_cmd.'timecost' : 'login';
//                    if (!($roomService->checkDuration() && $roomService->checkPassword())) {
//                        return JsonResponse::create([
//                            'status' => 0, 'data' => [
//                                'handle' => $handle,
//                                'rid' => $rid,
//                                'timecost' => $room['room_status'][6]['timecost'],
//                                'discount' => $room['discount']['discount'],
//                                'discountValue' => $room['discount']['discountValue'],
//                            ],
//                        ]);
//                    }
//                    break;
                case 8: //一对多
//                    $handle = $user ? $pwd_cmd . 'room_one_to_many' : 'login';
                    $handle = $user ? 'room_one_to_many' : 'login';
                    if (!$roomService->whiteList()) {
                        if ($h5 === 'h5hls') {
                            return JsonResponse::create([
                                'status' => 0, 'data' => [
                                    'handle' => $handle,
                                ]
                            ]);
                        }

                        $hplat = $redis->exists("hplatforms:$origin") ? "plat_whitename_room" : "not_whitename_room";


                        if ($hplat == 'plat_whitename_room') {
                            $uid = $user['uid'];
                            $plat_backurl = $roomService->getPlatUrl($origin);
                            $hplat_info = $redis->hgetall("hplatforms:$origin");

                            $logger->info("user exchange:  user id:$uid  origin:$origin ");
                            $hplat_user = $this->getMoney($uid, $rid, $origin);
                        }

                        return JsonResponse::create(['data' => [
                            //房间信息
                            'room' => &$room,
                            'user' => $user,
                            //一对多房间数据
                            'id' => $roomService->extend_room['onetomore'],
                            'rid' => $rid,
                            'points' => $roomService->extend_room['points'],
                            'start_time' => $roomService->extend_room['starttime'],
                            'end_time' => $roomService->extend_room['endtime'],
                            'duration' => strtotime($roomService->extend_room['endtime']) - strtotime($roomService->extend_room['starttime']),
                            'username' => resolve(UserService::class)->getUserByUid($rid)['nickname'],
                            'tickets' => isset($roomService->extend_room['tickets']) ? $roomService->extend_room['tickets'] : '',
                            'handle' => $handle,
                            //平台跳转信息
                            'plat_url' => json_encode($plat_backurl, JSON_FORCE_OBJECT),
                            //平台信息
                            'hplat_info' => json_encode($hplat_info, JSON_FORCE_OBJECT),
                            //平台用户信息
                            'hplat_user' => json_encode($hplat_user, JSON_FORCE_OBJECT),

                        ]]);
                    }

                    break;
                default:
                    ;
            }

//            if ($pwd_cmd && !($roomService->checkPassword())) {
//                $handle = $user ? $pwd_cmd : 'login';
//                $data = [
//                    'rid' => $rid,
//                    'handle' => $handle,
//                ];
//                return JsonResponse::create(['status' => 0, 'data' => $data]);
//            }
            /*经clack确认密码房间密码验证不需要5次错误增加验证码逻辑，直接在房间接口处理*/
            $pwd_cmd = $roomService->getPasswordRoom();
            //密码房的业务逻辑
            if ($pwd_cmd) {
                $password = $this->request()->input('password');
                if (empty($password) || $pwd_cmd != $this->decode($password)) {
                    return new JsonResponse([
                        'status' => 0,
                        'data' => [
                            'rid' => $rid,
                            'handle' => 'roompwd'
                        ],
                        'msg' => "密码不正确",
                    ]);
                }
            }
        }
        $channel_id = $chatServer['id'];
        $logger->info('in:' . $rid . ":" . Auth::id() . ':' . $channel_id);
        $qq_sideroom = $redis->hGet('hsite_config:' . SiteSer::siteId(), 'qq_sideroom');

        $plat_backurl = $roomService->getPlatUrl($origin);
        //$httphost = $roomService->getPlatHost();
        $data = [
            'room' => &$room,
            'handle' => 'common',
            'rid' => $rid,
            'origin' => $origin,
            'roomOrigin' => (int)($room['origin'] ?? 11),
            //平台跳转信息
            'plat_url' => json_encode($plat_backurl, JSON_FORCE_OBJECT),
            //平台信息
            'hplat_info' => json_encode($hplat_info, JSON_FORCE_OBJECT),
            //平台用户信息
            'hplat_user' => json_encode($hplat_user, JSON_FORCE_OBJECT),
            'in_limit_points' => SiteSer::config('in_limit_points') ?: 0,
            'in_limit_safemail' => SiteSer::config('in_limit_safemail') ?: 0,   //1开，0关
            'certificate' => $certificate,
            Session::getName() => Session::getId(),
            'uid' => Auth::id(),
            'nickname' => resolve(UserService::class)->getUserInfo($rid, 'nickname'),
            'channel_id' => $channel_id,
            'chat_fly_limit' => SiteSer::config('chat_fly_limit') ?: 0,
            'qq_sideroom' => $qq_sideroom,
        ];
        $data['chat_server_addr'] = $chat_server_addr;
        if ($h5 === 'h5') {
            unset($data['getRoomKey']);
            $httpStreaming = $this->getHTTPStreaming($rid);
            if (isset($httpStreaming['flv'])) {
                $data['flv_addr'] = $httpStreaming['flv'];
            } elseif (isset($httpStreaming['hls'])) {
                $data['hls_addr'] = $httpStreaming['hls'];
            } else {
                $data['http_streaming'] = 0;
            }
        } elseif ($h5 === 'h5hls') {
            unset($data['getRoomKey']);
            $data['status'] = 1;
            $data['chat_ws'] = $redis->smembers('schatws');
            $httpStreaming = $this->getHTTPStreaming($rid);
            $data['hls_addr'] = $httpStreaming['hls'];
        }
        return JsonResponse::create(['data' => $data]);
    }


    /**
     * 直播间中间页
     * @param int $roomid 房间ID
     * @param int $rid 房间类型
     * @param int $id 场次ID
     * @return static JsonResponse
     */
    public function roommid($roomid = 0, $rid = 0, $id = 0)
    {
        if (!$roomid || !$rid) {
            return JsonResponse::create(['status' => 0, 'msg' => '参数错误']);
        }
        $hostinfo = resolve(UserService::class)->getUserInfo($roomid);
        switch ($rid) {
            //密码房
            case 2:
                $roomService = resolve(RoomService::class);
                $pwd_cmd = $roomService->getPasswordRoom($roomid);
                if (null == $pwd_cmd) {
                    $data = [
                        'rid' => $roomid,
                        'handle' => 'common',
                    ];
                    return JsonResponse::create(['status' => 0, 'data' => $data, 'msg' => '密码房不存在']);
                } else {
                    $data = [
                        'rid' => $roomid,
                        'handle' => 'roompwd',
                    ];
                    return JsonResponse::create(['status' => 1, 'data' => $data]);
                }
//                $roomService->checkPassword($roomid);
            //一对一
            case 4:
                if (!$id) {
                    return JsonResponse::create(['status' => 0, 'msg' => '一对一缺少场次id信息']);
                }
                $one2one = resolve(One2OneRoomService::class);
                $one2one->set($roomid);
                $room = $one2one->getDataBykey($id);

                if (empty($room)) {
                    return JsonResponse::create(['status' => 0, 'msg' => '此一对一房间场次不存在']);
                } else {
                    if (strtotime($room['starttime']) + $room['duration'] < time()) {
                        return JsonResponse::create(['status' => 0, 'msg' => '该场次已经结束']);
                    }
                    if ($room['reuid']) {
                        $nowUserId = Auth::id();
                        if ($room['reuid'] == $nowUserId) {
                            $data = [
                                'rid' => $roomid,
                                'handle' => 'common',
                                'ticked' => 1
                            ];
                            return JsonResponse::create(['status' => 1, 'data' => $data, 'msg' => '您已经预约过该场次']);
                        } else {
                            $data = [
                                'rid' => $roomid,
                                'handle' => ''
                            ];
                            return JsonResponse::create(['status' => 0, 'data' => $data, 'msg' => '该场次已被预约']);
                        }
                    }
                    $room['rid'] = $roomid;
                    $room['handle'] = 'room_one_to_one';
                    $end_time = strtotime($room['starttime']) + $room['duration'];
                    $room['endtime'] = date('Y-m-d H:i:s', $end_time);
                    if (isset($hostinfo['nickname'])) {
                        $room['nickname'] = $hostinfo['nickname'];
                    } else {
                        $room['nickname'] = '';
                    }
                }
                return JsonResponse::create(['status' => 1, 'data' => $room]);
            //一对多
            case 7:
                if (!$id) {
                    return JsonResponse::create(['status' => 0, 'msg' => '一对多缺少场次id信息']);
                }
                $one2more = resolve(One2MoreRoomService::class);
                $one2more->set($roomid);
                $rooms = $one2more->getData();
                if (empty($rooms)) {
                    return JsonResponse::create(['status' => 0, 'msg' => '此一对多房间没有开任何场次']);
                }
                $nowUserId = Auth::id();
                foreach ($rooms as $v) {
                    if ($v['id'] == $id) {
                        if (strtotime($v['endtime']) < time()) {
                            return JsonResponse::create(['status' => 0, 'msg' => '该场次已经结束']);
                        }
                        $uidArr1 = [];
                        $uidArr2 = [];
                        if (!empty($v['uids'])) {
                            $uidArr1 = explode(',', $v['uids']);
                        }
                        if (!empty($v['tickets'])) {
                            $uidArr2 = explode(',', $v['tickets']);
                        }
                        $uidArr = array_merge($uidArr1, $uidArr2);
                        if (!empty($uidArr) && $nowUserId && in_array($nowUserId, $uidArr)) {
                            $data = [
                                'rid' => $roomid,
                                'handle' => 'common',
                            ];
                            return JsonResponse::create(['status' => 1, 'data' => $data, 'msg' => '您已经预约过该场次']);
                        }
                        $v['rid'] = $roomid;
                        $v['handle'] = 'room_one_to_many';
                        $origin = RoomOneToMore::query()->where('id', $id)->pluck('origin');
                        $v['origin'] = isset($origin[0]) ? $origin[0] : 12;
                        $v['duration'] = strtotime($v['endtime']) - strtotime($v['starttime']);
                        if (isset($hostinfo['nickname'])) {
                            $v['nickname'] = $hostinfo['nickname'];
                        } else {
                            $v['nickname'] = '';
                        }
                        return JsonResponse::create(['status' => 1, 'data' => $v]);
                    }
                }
            default:
                return JsonResponse::create(['status' => 0, 'msg' => '房间类型错误']);
        }

    }

    protected function isHost($rid)
    {
        return Auth::id() == $rid;
    }

    /**
     * @return mixed
     */
    private function getUserHost($chatServer, $isHost)
    {
        return explode('|', $chatServer['host'], 2)[$isHost ? 0 : 1];
    }

    public function getMoney($uid, $rid, $origin)
    {
        /**
         * @var \Redis $redis
         */
        $redis = $this->make('redis');
        $redis->del("hplat_user:$uid");

        /** 通知java获取*/
        $redis->publish('plat_money',
            json_encode([
                'origin' => $origin,
                'uid' => $uid,
                'rid' => $rid,
            ]));
        /** 检查购买状态 */
        $timeout = microtime(true) + 3;
        while (true) {
            if ($redis->exists("hplat_user:$uid")) break;
            if (microtime(true) > $timeout) break;
            usleep(100);
        }
        return $redis->exists("hplat_user:$uid") ? $redis->hgetall("hplat_user:" . $uid) : [];
    }

    /**
     * 获取房间的HTTP播放地址
     */
    public function getHTTPStreaming($rid)
    {
        $redis = resolve('redis');
        $host = $redis->hget('hvediosKtv:' . $rid, 'rtmp_host');
        $port = $redis->hget('hvediosKtv:' . $rid, 'rtmp_port') ?: "";

        $srtmp = $redis->sMembers('srtmp_server');
        $rtmp_up = "";
        foreach ($srtmp as $up) {
            if (preg_match(
                "/$host:?$port(.*)@@/"
                , $up
            )) {
                $rtmp_up = explode('@@', $up)[0];
                break;
            }
        }
        $sid = $redis->hget('hvedios_ktv_set:' . $rid, 'sid');
        $flv_down = $redis->smembers("srtmp_flv:$rtmp_up");
        $hls_down = $redis->smembers("srtmp_hls:$rtmp_up");

        $addr = [];
        if (is_array($flv_down)) {
            $addr['flv'] = str_replace('{SID}', $sid, $flv_down[0]);
        }
        if (is_array($hls_down)) {
            $addr['hls'] = str_replace('{SID}', $sid, $hls_down[0]);
        }

        // TODO: 防盜連 AUTH

        return $addr;
    }

    /**
     * @return page
     * @author Young
     * @des    用于合作平台测试
     */
    public function switchToOne2More()
    {

        $data = [
            'id' => '123',
            'rid' => '456',
            'points' => '789',
            'start_time' => '11:11',
            'end_time' => '12:12',
            'duration' => '30000',
            'username' => 'young',
        ];

        $plat_backurl = [
            'pay' => '/order/young',
        ];

        return $this->render('Room/plat_whitename_room', [
//          'room' => &$room,
            'data' => json_encode($data),
//          'handle' => $handle,
            'plat_url' => json_encode($plat_backurl, JSON_FORCE_OBJECT),
//          'hplat_info' => $hplat_info,
        ]);
    }

    /**
     * 获取房间的rtmp播放地址
     * @param $rid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getRTMP($rid)
    {
        $rtmp = $this->isHost($rid) ? $this->getUpstreamRTMP($rid) : $this->getDownstreamRTMP($rid);

        if (empty($rtmp['rtmp'])) {
            return JsonResponse::create(['status' => 0, 'msg' => '获取RTMP失败']);
        }
        /** 增加防盗链签名 */
//        $redis=$this->make('redis');
//        $rtmp_cnd_key = $redis->hget('hconf','rtmp_cdn_key');
//        $time = time();
//        $k = hash('md5', $rtmp_cnd_key . $time);
//        $rtmp = array_merge($rtmp, [
//            'k' => $k,
//            'time' => $time
//        ]);

        $tmp = [];
        if (!$this->isHost($rid)) {
            $certi = $this->make("safeService")->getLcertificate("cdn");
            foreach ($rtmp['rtmp'] as $v) {
                /**@var $rtmpObj RtmpService */
                $rtmpObj = resolve(RtmpService::class)->getRtmp($v, $rtmp['sid']);

                $ara = $rtmpObj->append(['certi' => $certi])->getParams();
                $tmp['sid'] = $rtmp['sid'] . ($ara ? '?' . $ara : "");
                $tmp['rtmp'][] = $v;
            }
        } else {
            $tmp = $rtmp;
        }
        $rtmp = json_encode($tmp, JSON_UNESCAPED_SLASHES);

        $method = 'AES-128-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $key = SiteSer::config('rtmp_secret_key');
        $ts = time();
        $digest = hash('sha256', session_id() . $key . $ts);
        $data = [
            base64_encode($iv),
            openssl_encrypt($rtmp, $method, $key, 0, $iv),
            $ts,
            base64_encode($digest),
        ];
        $data = join('.', $data);
        return JsonResponse::create()->setContent(json_encode(['status' => 1, 'data' => $data], JSON_UNESCAPED_SLASHES));
    }

    /**
     * 获取房间的rtmp上播地址
     * 如果开启，取用户自己的RTMP 反之取共用的srtmp_server
     * @param $rid
     * @return array
     */
    protected function getUpstreamRTMP($rid)
    {
        $rtmp = [];

        if ($this->useAnchorRTMP() == 1) {
            //OBS
            $tmp = $this->getUserRTMP();
            $rtmp = is_array($tmp) ? $tmp : [$tmp];
        } else {
            $rtmp = $this->getRTMPServers();
        }

        return [
            'rtmp' => $rtmp,
        ];
    }

    private function useAnchorRTMP()
    {
        return SiteSer::config('useanchorrtmp') ?: 1;
    }

    protected function getUserRTMP($uid = null)
    {
        return Auth::user()['rtmp_ip'] ?: $this->make('redis')->get('rtmp_ip') ?: [];
    }

    protected function getRTMPServers()
    {
        $redis = $this->make('redis');
        $srtmp = [];
        $srtmp = $redis->smembers('srtmp_server');
        if (empty($srtmp)) {
            $tmp = $redis->get('rtmp_live');
            if (!empty($tmp)) {
                $srtmp[] = $tmp;
            }
        }
        return $srtmp;
    }

    /**
     * 根据主播，获取房间的下播地址
     * @param $rid
     * @return array
     */
    protected function getDownstreamRTMP($rid)
    {
        /** @var \Redis $redis */
        $redis = $this->make('redis');
        $host = $redis->hget('hvediosKtv:' . $rid, 'rtmp_host');
        $port = $redis->hget('hvediosKtv:' . $rid, 'rtmp_port') ?: "";

        $srtmp = $redis->sMembers('srtmp_server');
        $rtmp_up = "";
        foreach ($srtmp as $up) {
            if (preg_match(
                "/$host:?$port(.*)@@/"
                , $up
            )) {
                $rtmp_up = explode('@@', $up)[0];
                break;
            }
        }
        //$rtmp_up = $port? "rtmp://$host:$port/proxypublish":"rtmp://$host/proxypublish";
        $rtmp_down = $redis->smembers("srtmp_user:$rtmp_up");

        //$certi = 'certi='.$this->make("safeService")->getLcertificate("cdn");
        return [
            'rtmp' => $rtmp_down,
            'sid' => $redis->hget('hvedios_ktv_set:' . $rid, 'sid'),
        ];
    }

    public function get($rid)
    {
        $data = $this->request()->get("data");
        $dataArr = explode('.', $data);
        $iv = base64_decode($dataArr[0]);
        $method = 'AES-128-CBC';
        $key = SiteSer::config('rtmp_secret_key');
        $t_data = openssl_decrypt($dataArr[1], $method, $key, 0, $iv);
        var_dump($t_data);
    }

    protected function getBroadcastType($uid = null)
    {
        return Auth::user()['broadcast_type'];
    }

    private function getRid($uid = null)
    {
        return resolve(UserService::class)->getUserInfo($uid ?: Auth::id(), 'rid');
    }
}