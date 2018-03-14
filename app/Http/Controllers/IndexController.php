<?php
namespace App\Http\Controllers;

use App\Models\RoomStatus;
use App\Models\UserBuyOneToMore;
use App\Models\UserGroupPermission;
use Symfony\Component\HttpFoundation\Cookie;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use App\Models\Users;
use App\Models\RoomDuration;
use App\Models\Pack;
use App\Models\Complaints;

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
        $uid = $this->request()->getSession()->get(self::SEVER_SESS_ID);
        $userinfo = $uid ? Users::find($uid)->toArray() :[];
        $ads = $this->make('redis')->hget('img_cache', 1);
        $ads = json_decode($ads, true);
        $slider = array();
        if($ads){
            foreach ($ads as $ad){
                if(filter_var($ad['url'], FILTER_VALIDATE_INT)){
                    $ad['url'] = 'http://'.rtrim(str_replace('www','v', $this->request()->getHost()), '/').'/'.$ad['url'];
                }else if(strstr($ad['url'], 'http://') || strstr($ad['url'], 'www.')){
                    $ad['url'] = 'http://'.ltrim($ad['url'],'http://');
                }else{
                    $ad['url'] = '/'.ltrim($ad['url'], '/');
                }
                $slider[] = $ad;
            }
        }
        $this->assign('ad_1', $slider);

        $ad_2 = $this->make('redis')->hget('img_cache', 2);
        $ad_2 = json_decode($ad_2, true);
        $this->assign('ad_2', $ad_2);
        //$userinfo['headimg'];
        //视频路径
        $video_info = $this->make('redis')->hget('img_cache','5');
        if($video_info){
            if(json_decode($video_info)){
                $video_info  = json_decode($video_info,true);
            }else{
                $downloadpcurl = null;
                $res = array(
                    'name'=>'',
                    'temp_name'=>'',
                    'url'=>''

                );
                $video_info   = json_encode($res);
            }
           //从一到十随机取数，
            $nums_video = rand(0,count($video_info));
            if(isset($video_info[$nums_video])){
                $video_url = "http://".$_SERVER['HTTP_HOST'].'/public/file/'.$video_info[$nums_video]['temp_name'];
                $jump_url = $video_info[$nums_video]['url'];
            }else{
                $video_url = "http://".$_SERVER['HTTP_HOST'].'/public/file/'.$video_info[0]['temp_name'];
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
        $type = $this->make('request')->get('type','all');

        //为什么总在$flashVer??
        //updata by Young
        //获取flash版本
        $flashVer = $this->make('redis')->get('flash_version');
        !$flashVer && $flashVer = 'v201504092044';

        //初始化$list数据
        $list = array(
            'rooms' => array()
        );

        /**
         * 首页中排行榜数据 TODO 修改调取接口
         */
        switch($type){

            case 'rank':
                $list = $this->make('redis')->get('home_js_data_' . $flashVer);
                $list = str_replace(array('cb(', ');'), array('', ''), $list);
                break;

            case 'fav':
                $uid = $this->_online;
                $myfavArr = $this->getUserAttensBycuruid($uid);
                $list = $this->make('redis')->get('home_all_' . $flashVer);
                $list = str_replace(array('cb(', ');'), array('', ''), $list);
                $data = json_decode($list, true);

                //echo $data; die;
                $myfav = array();
                if ($myfavArr) {
                    //过滤出主播
                    $hasharr = array();
                    foreach ($data['rooms'] as $value) {
                        $hasharr[$value['uid']] = $value;
                    }

                    foreach($myfavArr as $item){
                        if (isset($hasharr[$item])) {
                            $myfav[] = $hasharr[$item];
                        }
                    }
                }
                $data['rooms'] = $myfav;
                return JsonResponse::create($data);
                break;

            default:
                $list = $this->make('redis')->get('home_' . $type . '_' . $flashVer);
                $list = str_replace(array('cb(', ');'), '', $list);
        }

//        $list = json_decode($list, true);
        return JsonResponse::create()->setContent($list?:'{}');
    }

    public function registerAction()
    {
        if ($this->checkLogin()) {
            return new Response(
                json_encode(array(
                    "data" => 0,
                    "msg" => "已经在登录状态中！"
                )));
        }
        $sCode = $this->request()->get('sCode');
        if (!$this->container->config['config.SKIP_CAPTCHA_REG'] && (!$sCode || strtolower($sCode) != strtolower($this->_reqSession->get('CAPTCHA_KEY')))) {
            return new Response(
                json_encode(array(
                    "data" => 0,
                    "msg" => "验证码错误！请重新刷新"
                )));
        }
        //表单验证
        $username = $this->request()->get('username');
        if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $username) || strlen($username) < 5 || strlen($username) > 30) {
            return new Response(json_encode(array(
                "data" => 0,
                "msg" => "注册邮箱不符合格式！(5-30位的邮箱)"
            )));
        }
        if (!!$this->_findByUserName(array('username' => $username))) {
            return new Response(json_encode(array(
                "data" => 0,
                "msg" => "注册邮箱重复，请换一个试试！"
            )));
        }
        $nickname = $this->request()->get('nickname');
        //if( strlen($nickname) < 2 || strlen($nickname) > 16 || !preg_match("/^[A-Za-z0-9_".chr(0xa1)."-".chr(0xff)."]+[^_]$/",$nickname)|| !!$this->_findByUserName(array('nickname'=>$nickname)) ){
        $len = $this->count_chinese_utf8($nickname);
        //昵称不能使用/:;\空格,换行等符号。
        if ($len < 2 || $len > 8 || !preg_match("/^[^\s\/\:;]+$/", $nickname)) {
            return new Response(json_encode(array(
                "data" => 0,
                "msg" => "注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)"
            )));
        }

        if (!!$this->_findByUserName(array('nickname' => $nickname))) {
            return new Response(json_encode(array(
                "data" => 0,
                "msg" => "注册昵称重复，请换一个试试！"
            )));
        }
        $password = $this->request()->get('password');
        $repassword = $this->request()->get('repassword');
        if ($password != $repassword) {
            return new Response(json_encode(array(
                "data" => 0,
                "msg" => "两次输入的密码不相同！"
            )));
        }
        if (strlen($password) < 6 || strlen($password) > 22 || !preg_match('/^\w{6,22}$/', $password)) {
            return new Response(json_encode(array(
                "data" => 0,
                "msg" => "注册密码不符合格式！"
            )));
        }
        $uid = $this->_addUserInfo(array(
            'username' => $username,
            'nickname' => $nickname,
            'password' => md5($password),
            'created' => date('Y-m-d H:i:s')
        ));
        $userInfo = $this->_findByUserName(array(
            'uid' => $uid
        ));
        $this->writeRedis($userInfo);
        // $this->_setIndexCookie($userInfo,true);
        $this->_sendGift($uid, false, $this->container->config['config.REGISTER_SEND_POINT']);
        //用户邀请注册处理接口,Author By D.C 2014.12.10
        $this->_userRegisterByInvitation($uid);


        return new Response(json_encode(array(
            "data" => 1,
            "msg" => "恭喜您注册成功！"
        )));
    }

    /**
     * 设置用户进入时长房间状态
     * @author raby
     */
    public function setInRoomStat(){
        $roomid = $this->make('request')->get('roomid');
        if(!$roomid)  return new JsonResponse(array('code' => 2, 'msg' => '无效的参数'));

        $data = RoomStatus::where('uid', $roomid)
            ->where('tid', 6)->where('status', 1)->first();
        if(!$data)  return new JsonResponse(array('code' => 3, 'msg' => '不是时长房间'));

        $this->make('redis')->hset('htimecost_watch:' . $roomid, $this->_online, $roomid);
        return new JsonResponse(array('code' => 1, 'msg' => '设置状态成功'));
    }

    /**
     * @param string $type
     */
    public function checkUniqueName()
    {
        $type = $this->request()->get('type');
        $limitData = array('username', 'nickname');
        if (!in_array($type, $limitData)) {
            return new Response(json_encode(array(
                'msg' => '传递的参数非法！',
                'data' => 0
            )));
        }
        $username = $this->request()->get('username');
        if ($type == 'username') {
            if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $username) || strlen($username) < 5 || strlen($username) > 30) {
                return new Response(json_encode(array(
                    "data" => 0,
                    "msg" => "注册邮箱不符合格式！(5-30位的邮箱)"
                )));
            }
            $msg0 = '该邮箱已被使用，请换一个试试！';
            $msg1 = '恭喜该邮箱可以使用。';
            $userArr = array();
            $userArr = explode("@", $username);
            if ($this->make('redis')->hExists('husername_to_id', (count($userArr) == 2) ? $userArr[0] . "@" . strtolower($userArr[1]) : $username)) {
                return new Response(json_encode(array(
                    'msg' => $msg0,
                    'data' => 0
                )));
            } else {
                return new Response(json_encode(array(
                    'msg' => $msg1,
                    'data' => 1
                )));
            }
        } else {
            $len = $this->count_chinese_utf8($username);
            if ($len < 2 || $len > 8 || !preg_match("/^[^\s\/\:;]+$/", $username)) {
                return new Response(json_encode(array(
                    "data" => 0,
                    "msg" => "注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)"
                )));
            }
            $msg0 = '该昵称已被使用，请换一个试试！';
            $msg1 = '恭喜该昵称可以使用。';
        }
        $row = $this->_findByUserName(array($type => $username));
        if (!!$row) {
            return new Response(json_encode(array(
                'msg' => $msg0,
                'data' => 0
            )));
        } else {
            return new Response(json_encode(array(
                'msg' => $msg1,
                'data' => 1
            )));
        }
    }


    /**
     * @param $criteria
     * @return object
     */
    private function _findByUserName($criteria)
    {
        $data = Users::where($criteria)->first();
        return $data ? $data->toArray() : array();
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
     */
    public function getIndexInfo()
    {
        $remote_js_url = $GLOBALS['REMOTE_JS_URL'];
        $img_url = $this->container->config['config.REMOTE_PIC_URL'];
        $downloadUrl = $this->make('redis')->hget('hconf', 'down_url');
        $qrcode = $this->make('redis')->hget('img_cache','4');
        if(json_decode($downloadUrl)){
            $downloadpcurl  = json_decode($downloadUrl)->PC;
        }else{
            $downloadpcurl = null;
            $res = array(
                'PC'=>'',
                'ANDROID'=>'',
                'IOS'=>''
            );
            $downloadUrl   = json_encode($res);
        }

        if (!$this->checkLogin()) {
            setcookie(self::WEB_UID, null);
            return new Response(json_encode(
                array(
                    'ret' => false,
                    'info' => '没有登录！',
                    'js_url' => $remote_js_url,
                    'img_url' => $img_url,
                    'downloadAppurl' => $downloadUrl,
                    'downloadUrl'=> $downloadpcurl,
                    'qrcode_img' =>$qrcode
                )
            ));
        }
//        $this->_data_model = new DataModel($this);
        $uid = $this->request()->getSession()->get(self::SEVER_SESS_ID);
        $userinfo = Users::find($uid);
        //获取用户关注的信息
        if (!$userinfo) {
            return new Response(json_encode(array(
                'ret' => false,
                'info' => '无效的用户',
                'js_url' => $remote_js_url
            )));
        } else {
            $userinfo = $userinfo->toArray();//dc 增加转数组形式
            $uinfo = array(
                'uid' => $userinfo['uid'],
                'nickname' => urlencode($userinfo['nickname']),
                'headimg' => $this->getHeadimg($userinfo['headimg']),
                'points' => $userinfo['points'],
                'roled' => $userinfo['roled'],
                'rid' => $userinfo['rid'],
                'vip' => $userinfo['vip'],
                'vip_end' => $userinfo['vip_end'],
                'lv_rich' => $userinfo['lv_rich'],
                'lv_exp' => $userinfo['lv_exp'],
                'rid' => $userinfo['rid'],
                'safemail' => isset($userinfo['safemail']) ? urlencode($userinfo['safemail']) : '',
                'mails' => $this->make('messageServer')->getMessageNotReadCount($userinfo['uid'], $userinfo['lv_rich']),// 通过服务取到数量
                'icon_id' => intval($userinfo['icon_id']),
                'new_user' => $userinfo['created']>$this->container->config['config.USER_TIME_DIVISION']?1:0,
            );

            // 判断权限 隐身 贵族才有
            /*
            $groupServer = $this->make('userGroupServer');
            if ($uinfo['vip'] && $uinfo['roled']==0) {
                $group = $groupServer->getGroupByLevelIdAndType($uinfo['vip'], 'special');
                if ($group['permission']['allowstealth']) {
                    $uinfo['hidden'] = $userinfo->hidden;
                }
            }
            */
            /**
             * @author dc 修改，判断用户是否有隐身权限
             */
            if ($this->make('userServer')->getUserHiddenPermission($userinfo)) {
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
                $this->make('redis')->hSet('huser_info:' . $uid, 'vip', 0);
                $this->make('redis')->hSet('huser_info:' . $uid, 'vip_end', '');
                $this->make('redis')->del('user_car:' . $uid);
                $uinfo['vip'] = 0;
                $uinfo['vip_end'] = '';
            }

            //主播列表
            $arr = include(BASEDIR . '/app/cache/cli-files/anchor-search-data.php');
            $hasharr = array();
            foreach ($arr as $value) {
                $hasharr[$value['uid']] = $value;
            }
            unset($arr);

            // 获得房间地址
            $flashVersion = $this->make('redis')->get('flash_version');
            !$flashVersion && $flashVersion = 'v201504092044';
            $flashVer = $this->make('redis')->get('home_js_data_' . $flashVersion);
            if (!$flashVer) {
                return new Response(json_encode(array(
                    'ret' => false,
                    'info' => 'flashverison',
                    'js_url' => $remote_js_url
                )));
            };
            $flashVer = str_replace(array('cb(', ');'), array('', ''), $flashVer);
            $flashVer = json_decode($flashVer, true);
            $room_url = $flashVer['room_url'];//考虑做个redis的配置
            unset($flashVer);

            // 获取我的预约的房间
            $myres = array();
            $myReservation = RoomDuration::where('reuid', $uid)
                ->where('starttime', '>', date('Y-m-d H:i:s',time()-3600))
                ->orderBy('starttime', 'desc')
                ->get();


            if (!empty($myReservation)) {
                // 从redis 获取一对一预约数据
                $ordRooms = $this->make('redis')->get('home_ord_' . $flashVersion);
                $ordRooms = str_replace(array('cb(', ');'), array('', ''), $ordRooms);
                $ordRooms = json_decode($ordRooms, true);
                $rooms = $ordRooms['rooms'];//考虑做个redis的配置

                foreach ($myReservation as $item) {
                   foreach ($rooms as $room) {
                      if(($item->starttime)>date('Y-m-d H:i:s',time()-($item->duration))) {
                           if ($item->uid == $room['uid'] && $item->id == $room['id']) {
                               $room['listType'] = 'myres';
                               $myres[] = $room;
                           }
                      }
                    }
                }
            }


            $myticket = array();
            $oneToMore = UserBuyOneToMore::where('uid',$uid)->orderBy('starttime', 'desc')->get();//@TODO 时间过滤
            if (!empty($oneToMore)) {
                // 从redis 获取一对一预约数据
                $oneManyRooms = $this->make('redis')->get('home_one_many_' . $flashVersion);
                $oneManyRooms = str_replace(array('cb(', ');'), array('', ''), $oneManyRooms);
                $oneManyRooms = json_decode($oneManyRooms, true);
                $rooms = $oneManyRooms['rooms'];//考虑做个redis的配置
                if($rooms){
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
            $myfav = array();
            if (!!$myfavArr) {
                //过滤出主播
                foreach ($myfavArr as $item) {
                    if (isset($hasharr[$item])) {
                        $myfav[] = $hasharr[$item];
                    }
                }
            }

            return new Response(urldecode(json_encode(array(
                    'ret' => true,
                    'info' => $uinfo,
                    'myfav' => $myfav,
                    'myres' => $myres,
                    'myticket' => $myticket,
                    'img_url' => $img_url,
                    'js_url' => $remote_js_url,
                    'room_url' => $room_url,
                    'downloadAppurl' => $downloadUrl,
                    'downloadUrl'=> $downloadpcurl,
                    'qrcode_img' =>$qrcode
                )))
            );
        }
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

        $redis->hset('invitation', $invitation_uid, serialize(array('time' => time(), 'ips' => $ips)));

        //奖励点数
        $addPoints = 100;

        /**
         * 进行赠送奖励流程处理,调用【Gesila】接口。目的：实时更新数据库、Redis、房间状态。
         * @example /video_gs/web_api/add_point?uid=10000&points=1&act_name=invitation_user
         * @param uid = 用户ID，points=增加点数，act_name=活动名称
         * @return 1=成功，-102=增加失败，-103=来源IP没有权限，-104=用户不存在
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

        UserInvitation::create(array(
            'pid' => $invitation_uid,
            'uid' => $register_uid,
            'reward' => $addPoints,
            'fromip' => $ips,
            'fromtime' => date('Y-m-d H:i:s', time()),
            'fromlink' => $this->request()->cookies->get('invitation_refer')
        ));

        //邀请成功，发送消息通知
        $this->make('messageServer')->sendSystemToUsersMessage(array('rec_uid' => $invitation_uid, 'content' => '恭喜您成功邀请了一位新伙伴加入了蜜桃儿，并获得100个钻石的邀请奖励，要想获得更多奖励，请继续邀请您的亲朋好友们加入我们吧。'));
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
        $captcha = new \Video\ProjectBundle\Service\Captcha\Captcha();
        $captcha->width = 90;
        $captcha->height = 28;
        $image = $captcha->Generate();
        $headers = array(
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="' . $image . '"'
        );
        $this->get('session')->set('CAPTCHA_KEY', $captcha->phrase);
        return new Response($captcha->phrase . '.png', 200, $headers);
    }

    /**
     * 投诉建议
     * @return Response
     */
    public function complaints()
    {
        if (!$this->checkLogin()) {
            return new Response(json_encode(
                array(
                    'ret' => false,
                    'msg' => '还没有登录，请登录'
                )
            ));
        }
        $date = date('Y-m-d H:i:s');
        $today_nu = date('Ymd');
        $uid = $this->request()->getSession()->get(self::SEVER_SESS_ID);
        $hname = 'keys_complaints_flag:' . $uid . ':' . $today_nu;
        $times = $this->make('redis')->get($hname);
        if (empty($times)) {
            $this->make('redis')->set($hname, 1);
        } else {
            if ($times >= 10) return new Response(json_encode(
                array(
                    'ret' => false,
                    'times' => $times,
                    'msg' => '处理成功！'
                )
            ));
            $this->make('redis')->set($hname, $times + 1);
        }
        $data = array(
            'cid' => $this->request()->get('sername'),
            'uid' => $this->getCurUid($this->request()),
            'type' => intval($this->request()->get('sertype')),
            'content' => $this->request()->get('sercontent'),
            'created' => $date,
            'edit_time' => $date
        );
        if (!$data['content']) {
            return new Response(json_encode(
                array(
                    'ret' => false,
                    'times' => $times,
                    'msg' => '缺少投诉内容'
                )
            ));
        }
        Complaints::create($data);
        return new Response(json_encode(
            array(
                'ret' => true,
                'times' => $times,
                'msg' => '处理成功！'
            )
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
            return new JsonResponse(array('status' => 1, 'data' => 'data is not available!'));
        }
        $data = array();
        /*批量出来获取主播房间类型*/
        if ($action == 'getAnchorRoomType' && isset($_REQUEST['ridlists'])) {
            $ridlists = explode(',', $_REQUEST['ridlists']);
            if (!$ridlists) {
                return new JsonResponse(array('status' => 2, 'data' => 'data is not available!'));
            }

            foreach ($ridlists as $item) {
                $data[$item] = $this->getAnchorRoomType($item);
            }
        }
        return new JsonResponse(array('status' => 0, 'data' => $data));
    }
}
