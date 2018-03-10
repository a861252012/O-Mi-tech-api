<?php


namespace App\Http\Controllers;


use App\Service\Room\NoSocketChannelException;
use App\Service\Room\RoomService;
use App\Service\Room\SocketService;
use App\Service\Safe\RtmpService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RoomController extends Controller
{
    /**
     * 直播间首页
     * todo 追加打错日志
     * @param $rid
     * @return \Core\Response|JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index($rid, $h5 = false)
    {
        if ($this->make('config')['config.open_safe'])
            if (!$this->make('safeService')->auth($this->_online)) return new RedirectResponse('/');

        if ($rid <= 0) return $this->render('Room/no_room');
        $user = $this->make('userServer')->getUserByUid($rid);
        if (!$user || $user['roled'] != 3) return $this->render('Room/no_room');

        //get certificate
        $certificate = $this->make('safeService')->getLcertificate('socket');

        /** @var RoomService $roomService */
        $roomService = $this->make('roomService');
        //$xo_httphost = $roomService->getXOHost();
        //check kickout
        $timeleft = $roomService->checkKickOut($rid, $this->_online);
        if ($timeleft !== true) {
            return $this->render('Room/kicked', ['timeleft' => ceil($timeleft / 60)]);
        }
        $room = $roomService->getRoom($rid, $this->_online);
        /** @var SocketService $socketService */
        $socketService = $this->make('socketService');
        if (!empty($room) && !empty($room['channel_id'])) {
            $chatServer = $socketService->getServer($room['channel_id']);
        }
        if (empty($room) || empty($chatServer)) {
            //创建房间
            if ($this->userInfo['rid'] == $rid) {   //最近一次与当前一致
                try {
                    $room = $roomService->addRoom($rid, $rid, $this->_online);
                    $chatServer = $socketService->getServer($room['channel_id']);
                } catch (NoSocketChannelException $e) {
//                    die($e->getMessage());
                    return $this->render('Room/no_room');
                }
            }
        }
        $logPath = BASEDIR . '/app/logs/room' . date('Y-m') . '.log';
        $getRoomKey = '';
        if (!empty($chatServer)) {
            $host = $this->getUserHost($chatServer, $this->isHost($rid));
//            setrawcookie('room_host', $host . ':' . $chatServer['port'], time() + 604800, '/');  //一周
            //php setcookie 不支持写入逗号，直接调用header
            header('Set-Cookie: room_host="' . $host . ':' . $chatServer['port'] . '"; Max-Age=604800; Path=/');  //一周
            $getRoomKey = $host . ':' . $chatServer['port'];
            $this->make('systemServer')->logResult('enter_room:' . $rid . ":" . $this->_online . ':' . $room['channel_id'] . ':' . $getRoomKey, $logPath);
            header("Cache-Control: no-cache");
            header("Cache-Control: no-store");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        $redis = $this->make('redis');
        if (!isset($this->userInfo['origin'])) $this->userInfo['origin'] = 12;
        if (!isset($this->userInfo['created'])) $this->userInfo['created'] = "";
        $origin = $this->userInfo['origin'];

        if (!$this->isHost($rid)) {   //不是主播自己进自己房间
            $tid = $roomService->getCurrentRoomStatus();
            $this->make('systemServer')->logResult('current_tid:' . $tid, $logPath);
            switch ($tid) {
                case 4:   //一对一房间
                    $handle = $this->userInfo ? 'no_order' : 'login';
                    if (!$roomService->checkCanIn()) {
                        if ($h5 === 'h5hls')
                            return JsonResponse::create(['status' => 0]);
                        return $this->render('Room/no_order_room', [
                            'handle' => $handle
                        ]);
                    }
                    break;
                case 6:   //时长房间
                    $handle = $this->userInfo ? 'timecost' : 'login';
                    if (!$roomService->checkDuration()) {
                        if ($h5 === 'h5hls')
                            return JsonResponse::create(['status' => 0]);
                        return $this->render('Room/no_timecost_watch_room', [
                            'room' => &$room,
                            'handle' => $handle
                        ]);
                    }
                    break;
                case 7:   //时长房间和密码房
                    $handle = $this->userInfo ? 'roompwd|timecost' : 'login';
                    if (!($roomService->checkDuration() && $roomService->checkPassword())) {
                        if ($h5 === 'h5hls')
                            return JsonResponse::create(['status' => 0]);
                        return $this->render('Room/no_timecost_watch_pwd_room', [
                            'room' => &$room,
                            'handle' => $handle
                        ]);
                    }
                    break;
                case 8: //一对多
                    $handle = $this->userInfo ? 'room_one_to_many' : 'login';
                    if (!$roomService->whiteList()) {
                        if ($h5 === 'h5hls')
                            return JsonResponse::create(['status' => 0]);
                        $data = [
                            'id' => $roomService->extend_room['onetomore'],
                            'rid' => $rid,
                            'points' => $roomService->extend_room['points'],
                            'start_time' => $roomService->extend_room['starttime'],
                            'end_time' => $roomService->extend_room['endtime'],
                            'duration' => strtotime($roomService->extend_room['endtime']) - strtotime($roomService->extend_room['starttime']),
                            'username' => $this->make('userServer')->getUserByUid($rid)['nickname']
                        ];

                        $hplat = $redis->exists("hplatforms:$origin") ? "plat_whitename_room" : "not_whitename_room";
                        $hplat_user = [];
                        $plat_backurl = [];
                        $hplat_info = [];

                        if ($hplat == 'plat_whitename_room') {
                            $uid = $this->userInfo['uid'];
                            $plat_backurl = $roomService->getPlatUrl($origin);
                            $hplat_info = $redis->hgetall("hplatforms:$origin");

                            $logPath = BASEDIR . '/app/logs/' . date('Y-m') . '.log';
                            $this->logResult("user exchange:  user id:$uid  origin:$origin ", $logPath);
                            $hplat_user = $this->getMoney($uid, $rid, $origin);
                        }
//
//                        var_dump($this->userInfo);
//                        die;

                        return $this->render('Room/' . $hplat, [
                            //房间信息
                            'room' => &$room,
                            'user' => $this->userInfo,
                            //一对多房间数据
                            'data' => base64_encode(json_encode($data, JSON_FORCE_OBJECT)),
                            'handle' => $handle,
                            //平台跳转信息
                            'plat_url' => json_encode($plat_backurl, JSON_FORCE_OBJECT),
                            //平台信息
                            'hplat_info' => json_encode($hplat_info),
                            //平台用户信息
                            'hplat_user' => json_encode($hplat_user),
                        ]);
                    }

                    break;
                case 2:     //密码房间
                    $handle = $this->userInfo ? 'roompwd' : 'login';
                    if (!$roomService->checkPassword()) {
                        if ($h5 === 'h5hls')
                            return JsonResponse::create(['status' => 0]);
                        return $this->render('Room/no_passwd_room', [
                            'room' => &$room,
                            'handle' => $handle
                        ]);
                    }
                    break;
                default:
                    ;
            }
        }
        $channel_id = isset($room['channel_id']) ? $room['channel_id'] : 0;
        $this->make('systemServer')->logResult('in:' . $rid . ":" . $this->_online . ':' . $channel_id, $logPath);

        $plat_backurl = $roomService->getPlatUrl($origin);
        //$httphost = $roomService->getPlatHost();
        $data = [
            'userInfo' => $this->userInfo,
            'new_user' => $this->userInfo['created'] > $this->container->config['config.USER_TIME_DIVISION'] ? 1 : 0,
            'room' => &$room,
            'rid' => $rid,
            'origin' => $origin,
            'plat_url' => json_encode($plat_backurl, JSON_FORCE_OBJECT),
            'in_limit_points' => $redis->hget('hconf', 'in_limit_points') ?: 0,
            'in_limit_safemail' => $redis->hget('hconf', 'in_limit_safemail') ?: 0,   //1开，0关
            'flash_version' => $redis->get('flash_version') ?: 'v201504092044',
            'flash_ver_h5' => $redis->get('flash_ver_h5') ?: 'v201504092044',
            'certificate' => $certificate,
            'publish_version' => $redis->get('publish_version') ?: 'v2017090701' //young添加
        ];
        $data['getRoomKey'] = $getRoomKey;
        if (!$h5) {
            return $this->render('Room/room', $data);
        }
        if ($h5 === 'h5') {
            return $this->render('Room/roomh5', $data);
        }
        if ($h5 === 'h5hls') {
            unset($data['getRoomKey']);
            $data['status'] = 1;
            $data['chat_ws'] = $redis->smembers('schatws');
            $data['hls_addr'] = $this->getHLS($rid);
            return JsonResponse::create($data);
        }
    }

    private function getMoney($uid, $rid, $origin)
    {
        $redis = $this->make('redis');
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
            if (microtime(true) > $timeout) break;
            usleep(100);
        }
        return $redis->exists("hplat_user:$uid") ? $redis->hgetall("hplat_user:" . $uid) : [];
    }

    /**
     * @return page
     * @author Young
     * @des 用于合作平台测试
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
            'username' => 'young'
        ];

        $plat_backurl = [
            'pay' => '/order/young'
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
     * @return mixed
     */
    private function getUserHost($chatServer, $isHost)
    {
        return explode('|', $chatServer['host'], 2)[$isHost ? 0 : 1];
    }

    protected function isHost($rid)
    {
        return $this->_online == $rid;
    }

    /**
     * 获取房间的HLS播放地址
     */
    public function getHLS($rid)
    {
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
        $hls_down = $redis->smembers("srtmp_hls:$rtmp_up");

        //$certi = 'certi='.$this->make("safeService")->getLcertificate("cdn");
        $sid = $redis->hget('hvedios_ktv_set:' . $rid, 'sid');
        $addr = array_map(function ($hls) use ($sid, $redis) {
            $hls_arr = explode('@@', $hls);
            // 防盗链
            $url = $hls_arr[0] . '/' . $sid . '.m3u8';
            $uri = parse_url($url, PHP_URL_PATH);

            $cdnParams=[];
            switch ($hls_arr[1]){
                case 'superVIP:4':// 帝联
                    $cdn = $redis->hgetall('hrtmp_cdn:4');
                    $time = dechex(time());
                    $k = hash('md5', $cdn['key'] . $uri . $time);
                    $cdnParams=[
                        'k'=>$k,
                        't'=>$time
                    ];
                    break;
                default:
                    break;
            }
            return [
                'addr' => empty($cdnParams)?$url:$url.'?'.http_build_query($cdnParams),
                'name' => $hls_arr[1]
            ];
        }, $hls_down);
        return $addr;
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
                $rtmpObj = $this->make('rtmpService')->getRtmp($v, $rtmp['sid']);

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
        $key = $this->make('config')->get('config.RTMP_SECRET_KEY');
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

    public function get($rid)
    {
        $data = $this->request()->get("data");
        $dataArr = explode('.', $data);
        $iv = base64_decode($dataArr[0]);
        $method = 'AES-128-CBC';
        $key = $this->make('config')->get('config.RTMP_SECRET_KEY');
        $t_data = openssl_decrypt($dataArr[1], $method, $key, 0, $iv);
        var_dump($t_data);
    }

    /**
     * 获取房间的rtmp上播地址
     * 如果开启，取用户自己的RTMP（huser_info） 反之取共用的srtmp_server
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
            'rtmp' => $rtmp
        ];
    }

    private function useAnchorRTMP()
    {
        return $this->make('config')->get('config.useAnchorRTMP', 1);
    }

    protected function getUserRTMP($uid = null)
    {
        return $this->userInfo['rtmp_ip'] ?: $this->make('redis')->get('rtmp_ip') ?: [];
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
            'sid' => $redis->hget('hvedios_ktv_set:' . $rid, 'sid')
        ];
    }

    protected function parseXOUrl($url, $xo_httphost)
    {
        if (!$xo_httphost) return $url;
        $url = parse_url($url);
        $xo_httphost = parse_url($xo_httphost);
        $parse_url = array_merge($url, $xo_httphost);
        if (empty($xo_httphost['port']))
            unset($parse_url['port']);
        return
            ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : 'http://')
            . ((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') . '@' : '')
            . ((isset($parse_url['host'])) ? $parse_url['host'] : '')
            . ((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
            . ((isset($parse_url['path'])) ? $parse_url['path'] : '')
            . ((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
            . ((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '');
    }

    protected function getBroadcastType($uid = null)
    {
        return $this->userInfo['broadcast_type'];
    }

    private function getRid($uid = null)
    {
        return $this->make('redis')->hget('huser_info:' . $uid ?: $this->_online, 'rid');
    }
}