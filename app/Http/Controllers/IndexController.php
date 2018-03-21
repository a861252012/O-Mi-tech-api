<?php

namespace App\Http\Controllers;

use App\Models\Complaints;
use App\Models\Pack;
use App\Models\RoomDuration;
use App\Models\RoomStatus;
use App\Models\UserBuyOneToMore;
use App\Models\Users;
use App\Services\Message\MessageService;
use App\Services\User\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends Controller
{

    public function indexAction()
    {
        //如果登陆了显示登陆数据
//        $userInfo = array();
//        if ($this->checkLogin()) {
//            $session = $this->get('Request')->getSession();
//            $uid = $session->get(self::SEVER_SESS_ID);
//            $userInfo = $this->getRedis()->hgetall('huser_info:' . $uid);
//        }
//        $baseUrl = $this->get('Request')->getRequestUri();
//        $baseUrl = explode('/', substr($baseUrl, 1));
//        $rtnArr = array(
//            'baseUrl' => $baseUrl[0],
//            'userInfo' => $this->getOutputUser($userInfo),
//            'remoteUrl' => $GLOBALS['REMOTE_JS_URL']
//        );
        // echo '这是新首页';

        //等待halin优化
        $uid = session(self::SEVER_SESS_ID);
        $userinfo = $uid ? Users::find($uid)->toArray() : [];
        $ads = Redis::hget('img_cache', 1);
        $ads = json_decode($ads, true);
        $slider = [];
        if ($ads) {
            foreach ($ads as $ad) {
                if (filter_var($ad['url'], FILTER_VALIDATE_INT)) {
                    $ad['url'] = 'http://' . rtrim(str_replace('www', 'v', $this->request()->getHost()), '/') . '/' . $ad['url'];
                } else if (strstr($ad['url'], 'http://') || strstr($ad['url'], 'www.')) {
                    $ad['url'] = 'http://' . ltrim($ad['url'], 'http://');
                } else {
                    $ad['url'] = '/' . ltrim($ad['url'], '/');
                }
                $slider[] = $ad;
            }
        }
        $this->assign('ad_1', $slider);

        $ad_2 = Redis::hget('img_cache', 2);
        $ad_2 = json_decode($ad_2, true);
        $this->assign('ad_2', $ad_2);
        //$userinfo['headimg'];
        //视频路径
        $video_info = Redis::hget('img_cache', '5');
        if ($video_info) {
            if (json_decode($video_info)) {
                $video_info = json_decode($video_info, true);
            } else {
                $downloadpcurl = null;
                $res = [
                    'name' => '',
                    'temp_name' => '',
                    'url' => '',

                ];
                $video_info = json_encode($res);
            }
            //从一到十随机取数，
            $nums_video = rand(0, count($video_info));
            if (isset($video_info[$nums_video])) {
                $video_url = "http://" . $_SERVER['HTTP_HOST'] . '/public/file/' . $video_info[$nums_video]['temp_name'];
                $jump_url = $video_info[$nums_video]['url'];
            } else {
                $video_url = "http://" . $_SERVER['HTTP_HOST'] . '/public/file/' . $video_info[0]['temp_name'];
                $jump_url = $video_info[0]['url'];
            }

            //返回视频地址和视频超链接
            $this->assign('video_url', $video_url);
            $this->assign('jump_url', $jump_url);

        }

        return $this->render('Index/index', $userinfo);

    }

    /**
     * 首页数据获取的地址
     * type 是获取的种类
     *
     * @return Response|void
     */
    public function videoList()
    {
        //$type 判断
        $type = request()->get('type', 'all');

        //为什么总在$flashVer??
        //updata by Young
        //获取flash版本
        $flashVer = Redis::get('flash_version');
        !$flashVer && $flashVer = 'v201504092044';

        //初始化$list数据
        $list = [
            'rooms' => [],
        ];

        /**
         * 首页中排行榜数据 TODO 修改调取接口
         */
        switch ($type) {

            case 'rank':
                $list = Redis::get('home_js_data_' . $flashVer);
                $list = str_replace(['cb(', ');'], ['', ''], $list);
                break;

            case 'fav':
                $uid = Auth::id();
                $myfavArr = $this->getUserAttensBycuruid($uid);
                $list = Redis::get('home_all_' . $flashVer);
                $list = str_replace(['cb(', ');'], ['', ''], $list);
                $data = json_decode($list, true);

                //echo $data; die;
                $myfav = [];
                if ($myfavArr) {
                    //过滤出主播
                    $hasharr = [];
                    foreach ($data['rooms'] as $value) {
                        $hasharr[$value['uid']] = $value;
                    }

                    foreach ($myfavArr as $item) {
                        if (isset($hasharr[$item])) {
                            $myfav[] = $hasharr[$item];
                        }
                    }
                }
                $data['rooms'] = $myfav;
                return JsonResponse::create($data);
                break;

            default:
                $list = Redis::get('home_' . $type . '_' . $flashVer);
                $list = str_replace(['cb(', ');'], '', $list);
        }

//        $list = json_decode($list, true);
        return JsonResponse::create()->setContent($list ?: '{}');
    }

    public function registerAction()
    {
        if (Auth::check()) {
            return new Response(
                json_encode([
                    "data" => 0,
                    "msg" => "已经在登录状态中！",
                ]));
        }
        $sCode = $this->request()->get('sCode');
        if (!$this->container->config['config.SKIP_CAPTCHA_REG'] && (!$sCode || strtolower($sCode) != strtolower($this->_reqSession->get('CAPTCHA_KEY')))) {
            return new Response(
                json_encode([
                    "data" => 0,
                    "msg" => "验证码错误！请重新刷新",
                ]));
        }
        //表单验证
        $username = $this->request()->get('username');
        if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $username) || strlen($username) < 5 || strlen($username) > 30) {
            return new Response(json_encode([
                "data" => 0,
                "msg" => "注册邮箱不符合格式！(5-30位的邮箱)",
            ]));
        }
        if (!!$this->_findByUserName(['username' => $username])) {
            return new Response(json_encode([
                "data" => 0,
                "msg" => "注册邮箱重复，请换一个试试！",
            ]));
        }
        $nickname = $this->request()->get('nickname');
        //if( strlen($nickname) < 2 || strlen($nickname) > 16 || !preg_match("/^[A-Za-z0-9_".chr(0xa1)."-".chr(0xff)."]+[^_]$/",$nickname)|| !!$this->_findByUserName(array('nickname'=>$nickname)) ){
        $len = $this->count_chinese_utf8($nickname);
        //昵称不能使用/:;\空格,换行等符号。
        if ($len < 2 || $len > 8 || !preg_match("/^[^\s\/\:;]+$/", $nickname)) {
            return new Response(json_encode([
                "data" => 0,
                "msg" => "注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)",
            ]));
        }

        if (!!$this->_findByUserName(['nickname' => $nickname])) {
            return new Response(json_encode([
                "data" => 0,
                "msg" => "注册昵称重复，请换一个试试！",
            ]));
        }
        $password = $this->request()->get('password');
        $repassword = $this->request()->get('repassword');
        if ($password != $repassword) {
            return new Response(json_encode([
                "data" => 0,
                "msg" => "两次输入的密码不相同！",
            ]));
        }
        if (strlen($password) < 6 || strlen($password) > 22 || !preg_match('/^\w{6,22}$/', $password)) {
            return new Response(json_encode([
                "data" => 0,
                "msg" => "注册密码不符合格式！",
            ]));
        }
        $uid = $this->_addUserInfo([
            'username' => $username,
            'nickname' => $nickname,
            'password' => md5($password),
            'created' => date('Y-m-d H:i:s'),
        ]);
        $userInfo = $this->_findByUserName([
            'uid' => $uid,
        ]);
        $this->writeRedis($userInfo);
        // $this->_setIndexCookie($userInfo,true);
        $this->_sendGift($uid, false, $this->container->config['config.REGISTER_SEND_POINT']);
        //用户邀请注册处理接口,Author By D.C 2014.12.10
        $this->_userRegisterByInvitation($uid);


        return new Response(json_encode([
            "data" => 1,
            "msg" => "恭喜您注册成功！",
        ]));
    }

    /**
     * 设置用户进入时长房间状态
     * @author raby
     */
    public function setInRoomStat()
    {
        $roomid = $this->make('request')->get('roomid');
        if (!$roomid) return new JsonResponse(['code' => 2, 'msg' => '无效的参数']);

        $data = RoomStatus::where('uid', $roomid)
            ->where('tid', 6)->where('status', 1)->first();
        if (!$data) return new JsonResponse(['code' => 3, 'msg' => '不是时长房间']);

        Redis::hset('htimecost_watch:' . $roomid, Auth::id(), $roomid);
        return new JsonResponse(['code' => 1, 'msg' => '设置状态成功']);
    }

    /**
     * @param string $type
     */
    public function checkUniqueName()
    {
        $type = $this->request()->get('type');
        $limitData = ['username', 'nickname'];
        if (!in_array($type, $limitData)) {
            return new Response(json_encode([
                'msg' => '传递的参数非法！',
                'data' => 0,
            ]));
        }
        $username = $this->request()->get('username');
        if ($type == 'username') {
            if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $username) || strlen($username) < 5 || strlen($username) > 30) {
                return new Response(json_encode([
                    "data" => 0,
                    "msg" => "注册邮箱不符合格式！(5-30位的邮箱)",
                ]));
            }
            $msg0 = '该邮箱已被使用，请换一个试试！';
            $msg1 = '恭喜该邮箱可以使用。';
            $userArr = [];
            $userArr = explode("@", $username);
            if (Redis::hExists('husername_to_id', (count($userArr) == 2) ? $userArr[0] . "@" . strtolower($userArr[1]) : $username)) {
                return new Response(json_encode([
                    'msg' => $msg0,
                    'data' => 0,
                ]));
            } else {
                return new Response(json_encode([
                    'msg' => $msg1,
                    'data' => 1,
                ]));
            }
        } else {
            $len = $this->count_chinese_utf8($username);
            if ($len < 2 || $len > 8 || !preg_match("/^[^\s\/\:;]+$/", $username)) {
                return new Response(json_encode([
                    "data" => 0,
                    "msg" => "注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)",
                ]));
            }
            $msg0 = '该昵称已被使用，请换一个试试！';
            $msg1 = '恭喜该昵称可以使用。';
        }
        $row = $this->_findByUserName([$type => $username]);
        if (!!$row) {
            return new Response(json_encode([
                'msg' => $msg0,
                'data' => 0,
            ]));
        } else {
            return new Response(json_encode([
                'msg' => $msg1,
                'data' => 1,
            ]));
        }
    }


    /**
     * @param $criteria
     * @return object
     */
    private function _findByUserName($criteria)
    {
        $data = Users::where($criteria)->first();
        return $data ? $data->toArray() : [];
    }


    /**
     * @param $criteria
     * @return mixed
     */
    private function _addUserInfo($criteria)
    {
        $user = Users::create($criteria);
        return $user->id;
    }

    /**
     * todo 可删除关注部分的代码
     * @return Response
     * @throws \Exception
     */
    public function getIndexInfo()
    {
        $return = [];
        $uid = Auth::id();
        $userinfo = Auth::user();
        $ads = Redis::hget('img_cache', 1);
        $ads = json_decode($ads, true);
        $slider = [];
        if ($ads) {
            foreach ($ads as $ad) {
                if (filter_var($ad['url'], FILTER_VALIDATE_INT)) {
                    $ad['url'] = '/' . $ad['url'];
                } else {
                    $ad['url'] = '/' . ltrim($ad['url'], '/');
                }
                $slider[] = $ad;
            }
        }
        $return['ad_1'] = $slider;
        $ad_2 = Redis::hget('img_cache', 2);
        $ad_2 = json_decode($ad_2, true);
        $return['ad_2'] = $ad_2;
        //视频路径
        $video_info = Redis::hget('img_cache', '5');
        if ($video_info) {
            if (json_decode($video_info)) {
                $video_info = json_decode($video_info, true);
            } else {
                $downloadpcurl = null;
                $res = [
                    'name' => '',
                    'temp_name' => '',
                    'url' => '',
                ];
                $video_info = json_encode($res);
            }
            //从一到十随机取数，
            $nums_video = rand(0, count($video_info));
            if (isset($video_info[$nums_video])) {
                $video_url = request()->getSchemeAndHttpHost() . '/public/file/' . $video_info[$nums_video]['temp_name'];
                $jump_url = $video_info[$nums_video]['url'];
            } else {
                $video_url = request()->getSchemeAndHttpHost() . '/public/file/' . $video_info[0]['temp_name'];
                $jump_url = $video_info[0]['url'];
            }

            //返回视频地址和视频超链接
            $return['video_url'] = $video_url;
            $return['jump_url'] = $jump_url;
        }

        $downloadUrl = Redis::hget('hconf', 'down_url');
        $qrcode = Redis::hget('img_cache', '4');
        if (json_decode($downloadUrl)) {
            $downloadpcurl = json_decode($downloadUrl)->PC;
        } else {
            $downloadpcurl = null;
            $res = [
                'PC' => '',
                'ANDROID' => '',
                'IOS' => '',
            ];
            $downloadUrl = json_encode($res);
        }

        if (!Auth::guard('pc')->check()) {
            return JsonResponse::create(array_merge($return, [
                'ret' => false,
                'info' => '没有登录！',
                'downloadAppurl' => $downloadUrl,
                'downloadUrl' => $downloadpcurl,
                'qrcode_img' => $qrcode,
            ]));
        }
        //TODO 优化
        //获取用户关注的信息
        if (!$userinfo) {
            return JsonResponse::create([
                'ret' => false,
                'info' => '无效的用户',
            ]);
        }
        $uinfo = [
            'uid' => $userinfo['uid'],
            'nickname' => $userinfo['nickname'],
            'headimg' => $this->getHeadimg($userinfo['headimg']),
            'points' => $userinfo['points'],
            'roled' => $userinfo['roled'],
            'rid' => $userinfo['rid'],
            'vip' => $userinfo['vip'],
            'vip_end' => $userinfo['vip_end'],
            'lv_rich' => $userinfo['lv_rich'],
            'lv_exp' => $userinfo['lv_exp'],
            'safemail' => $userinfo['safemail'] ?? '',
            'mails' => resolve(MessageService::class)->getMessageNotReadCount($userinfo['uid'], $userinfo['lv_rich']),// 通过服务取到数量
            'icon_id' => intval($userinfo['icon_id']),
            'new_user' => $userinfo['created'] > config('config.USER_TIME_DIVISION') ? 1 : 0,
        ];

        /**
         * @author dc 修改，判断用户是否有隐身权限
         */
        if (resolve(UserService::class)->getUserHiddenPermission($userinfo)) {
            $uinfo['hidden'] = $userinfo['hidden'];
        }


        if (isset($_COOKIE[self::WEB_UID]) && $uid != $_COOKIE[self::WEB_UID]) {
            $this->setCookieByDomain(self::WEB_UID, $uid);
        } elseif (!isset($_COOKIE[self::WEB_UID])) {
            $this->setCookieByDomain(self::WEB_UID, $uid);
        }

        // 是贵族才验证 下掉贵族状态
        if ($uinfo['vip'] && ($uinfo['vip_end'] < date('Y-m-d H:i:s'))) {
            $vip_user = Users::find($uinfo['uid']);
            $vip_user->vip = 0;
            $vip_user->vip_end = '0000-00-00 00:00:00';
            $vip_user->save();

            // 删除坐骑
            Pack::where('uid', $uid)->where('gid', '<=', 120107)->where('gid', '>=', 120101)->delete();
            Redis::hSet('huser_info:' . $uid, 'vip', 0);
            Redis::hSet('huser_info:' . $uid, 'vip_end', '');
            Redis::del('user_car:' . $uid);
            $uinfo['vip'] = 0;
            $uinfo['vip_end'] = '';
        }

        //主播列表
        $arr = include Storage::path('cache/anchor-search-data.php');
        $hasharr = [];
        foreach ($arr as $value) {
            $hasharr[$value['uid']] = $value;
        }
        unset($arr);

        // 获得房间地址
        $flashVersion = Redis::get('flash_version');
        !$flashVersion && $flashVersion = 'v201504092044';
        $flashVer = Redis::get('home_js_data_' . $flashVersion);
        if (!$flashVer) {
            return JsonResponse::create([
                'ret' => false,
                'info' => 'flashverison',
            ]);
        };
        $flashVer = str_replace(['cb(', ');'], ['', ''], $flashVer);
        $flashVer = json_decode($flashVer, true);
        $room_url = $flashVer['room_url'];//考虑做个redis的配置
        unset($flashVer);

        // 获取我的预约的房间
        $myres = [];
        $myReservation = RoomDuration::where('reuid', $uid)
            ->where('starttime', '>', date('Y-m-d H:i:s', time() - 3600))
            ->orderBy('starttime', 'desc')
            ->get();


        if (!empty($myReservation)) {
            // 从redis 获取一对一预约数据
            $ordRooms = Redis::get('home_ord_' . $flashVersion);
            $ordRooms = str_replace(['cb(', ');'], ['', ''], $ordRooms);
            $ordRooms = json_decode($ordRooms, true);
            $rooms = $ordRooms['rooms'];//考虑做个redis的配置

            foreach ($myReservation as $item) {
                foreach ($rooms as $room) {
                    if (($item->starttime) > date('Y-m-d H:i:s', time() - ($item->duration))) {
                        if ($item->uid == $room['uid'] && $item->id == $room['id']) {
                            $room['listType'] = 'myres';
                            $myres[] = $room;
                        }
                    }
                }
            }
        }


        $myticket = [];
        $oneToMore = UserBuyOneToMore::where('uid', $uid)->orderBy('starttime', 'desc')->get();//@TODO 时间过滤
        if (!empty($oneToMore)) {
            // 从redis 获取一对一预约数据
            $oneManyRooms = Redis::get('home_one_many_' . $flashVersion);
            $oneManyRooms = str_replace(['cb(', ');'], ['', ''], $oneManyRooms);
            $oneManyRooms = json_decode($oneManyRooms, true);
            $rooms = $oneManyRooms['rooms'];//考虑做个redis的配置
            if ($rooms) {
                foreach ($oneToMore as $item) {
                    foreach ($rooms as $room) {
                        if ($item->rid == $room['uid'] && $item->onetomore == $room['id']) {
                            $room['listType'] = 'myticket';
                            //$room['tid'] = 1;
                            //$room['live_time'] = $room['start_time'];
                            $myticket[] = $room;
                        }
                    }
                }
            }
        }

        // 获取我的关注的数据主播的数据
        $myfavArr = $this->getUserAttensBycuruid($uid);
        $myfav = [];
        if (!!$myfavArr) {
            //过滤出主播
            foreach ($myfavArr as $item) {
                if (isset($hasharr[$item])) {
                    $myfav[] = $hasharr[$item];
                }
            }
        }

        return JsonResponse::create([
            'ret' => true,
            'info' => $uinfo,
            'myfav' => $myfav,
            'myres' => $myres,
            'myticket' => $myticket,
            'room_url' => $room_url,
            'downloadAppurl' => $downloadUrl,
            'downloadUrl' => $downloadpcurl,
            'qrcode_img' => $qrcode,
        ]);

    }

    /**
     * 获取时间差的时分秒
     * @Author Orino
     */
    public function Hms($microtime)
    {
        $cha = (time() * 1000 - $microtime) / 1000;
        $hour = floor($cha / 60 / 60);
        $minute = floor(($cha - $hour * 60 * 60) / 60);
        $second = floor($cha - $hour * 60 * 60 - $minute * 60);
        $str = '';
        if ($hour > 0) {
            $str .= $hour . '时';
        }
        if ($minute > 0) {
            $str .= $minute . '分';
        }
        if ($second > 0) {
            $str .= $second . '秒';
        }
        return $str;
    }

    /**
     * 邀请注册推广用户处理逻辑方法
     * @author D.C
     * @update 2014.12.12
     * @param int $register_uid
     * @return bool
     */
    private function _userRegisterByInvitation($register_uid = 0)
    {

        //用户推广链接活动开关
        if (!$this->container->config['config.INVITATION_STATUS']) return false;

        //注册失败
        if (!$register_uid) return false;

        //检查Cookie是否存在,并获取推广人ID
        $invitation_uid = $this->request()->cookies->get('invitation_uid');
        if (!$invitation_uid) return false;

        //获取客户端IP，用于反作弊预处理
        $ips = $this->request()->getClientIp();

        //初始化Redis对象
        $redis = $this->make('redis');

        //防作弊处理,暂时未启用
        /*
        if($redis->hexists('invitation', $invitation_uid)){
            $i_user = unserialize($redis->hget('invitation', $invitation_uid));
            //一小时内同IP注册视为作弊，过滤处理
            if( ($i_user['time']+3600)<time() &&  $i_user['ips'] == $ips ){
                //return false;
            }
        }
        */

        $redis->hset('invitation', $invitation_uid, serialize(['time' => time(), 'ips' => $ips]));

        //奖励点数
        $addPoints = 100;

        /**
         * 进行赠送奖励流程处理,调用【Gesila】接口。目的：实时更新数据库、Redis、房间状态。
         * @example /video_gs/web_api/add_point?uid=10000&points=1&act_name=invitation_user
         * @param uid = 用户ID，points=增加点数，act_name=活动名称
         * @return  1=成功，-102=增加失败，-103=来源IP没有权限，-104=用户不存在
         */
        $apiURL = rtrim($GLOBALS['REMOTE_JS_URL'], '/') . '/video_gs/web_api/add_point?uid=' . $invitation_uid . '&points=' . $addPoints . '&act_name=invitation_user';
        $getPointsStatus = null;
        try {
            $getPointsStatus = file_get_contents($apiURL);
        } catch (\Exception $e) {
            return false;
        }
        $getPointsStatus = json_decode($getPointsStatus);

        //奖励失败处理
        if (is_null($getPointsStatus) || !$getPointsStatus->ret) {
            return false;
        }

        //数据库记录操作处理
        //为了优化性能不查询已存在用户

        UserInvitation::create([
            'pid' => $invitation_uid,
            'uid' => $register_uid,
            'reward' => $addPoints,
            'fromip' => $ips,
            'fromtime' => date('Y-m-d H:i:s', time()),
            'fromlink' => $this->request()->cookies->get('invitation_refer'),
        ]);

        //邀请成功，发送消息通知
        $this->make('messageServer')->sendSystemToUsersMessage(['rec_uid' => $invitation_uid, 'content' => '恭喜您成功邀请了一位新伙伴加入了蜜桃儿，并获得100个钻石的邀请奖励，要想获得更多奖励，请继续邀请您的亲朋好友们加入我们吧。']);
        return true;
    }


    /**
     * 验证码方法
     * 获取输出图形验证码
     * @author D.C
     * @return Response
     */
    public function captchaAction()
    {
        $captcha = resolve('captcha');
        $captcha->width = 90;
        $captcha->height = 28;
        $image = $captcha->Generate();
        $headers = [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="' . $image . '"',
        ];
        $this->get('session')->set('CAPTCHA_KEY', $captcha->phrase);
        return new Response($captcha->phrase . '.png', 200, $headers);
    }

    /**
     * 投诉建议
     * @return Response
     */
    public function complaints()
    {
        if (Auth::guest()) {
            return new Response(json_encode(
                [
                    'ret' => false,
                    'msg' => '还没有登录，请登录',
                ]
            ));
        }
        $date = date('Y-m-d H:i:s');
        $today_nu = date('Ymd');
        $uid = $this->request()->getSession()->get(self::SEVER_SESS_ID);
        $hname = 'keys_complaints_flag:' . $uid . ':' . $today_nu;
        $times = Redis::get($hname);
        if (empty($times)) {
            Redis::set($hname, 1);
        } else {
            if ($times >= 10) return new Response(json_encode(
                [
                    'ret' => false,
                    'times' => $times,
                    'msg' => '处理成功！',
                ]
            ));
            Redis::set($hname, $times + 1);
        }
        $data = [
            'cid' => $this->request()->get('sername'),
            'uid' => $this->getCurUid($this->request()),
            'type' => intval($this->request()->get('sertype')),
            'content' => $this->request()->get('sercontent'),
            'created' => $date,
            'edit_time' => $date,
        ];
        if (!$data['content']) {
            return new Response(json_encode(
                [
                    'ret' => false,
                    'times' => $times,
                    'msg' => '缺少投诉内容',
                ]
            ));
        }
        Complaints::create($data);
        return new Response(json_encode(
            [
                'ret' => true,
                'times' => $times,
                'msg' => '处理成功！',
            ]
        ));
    }

    /**
     * php-cli通过curl调用获取,通过内部访问，维护代码方便
     */
    public function cliGetRes()
    {
        $action = $this->request()->get('act');
        if ($action == null || !isset($_REQUEST['vsign']) || $this->container->config['config.VFPHP_SIGN'] != $_REQUEST['vsign'] ||
            !method_exists($this, $action)
        ) {
            return new JsonResponse(['status' => 1, 'data' => 'data is not available!']);
        }
        $data = [];
        /*批量出来获取主播房间类型*/
        if ($action == 'getAnchorRoomType' && isset($_REQUEST['ridlists'])) {
            $ridlists = explode(',', $_REQUEST['ridlists']);
            if (!$ridlists) {
                return new JsonResponse(['status' => 2, 'data' => 'data is not available!']);
            }

            foreach ($ridlists as $item) {
                $data[$item] = $this->getAnchorRoomType($item);
            }
        }
        return new JsonResponse(['status' => 0, 'data' => $data]);
    }
}
