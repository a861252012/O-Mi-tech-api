<?php

namespace App\Http\Controllers;

use App\Models\ActivityClick;
use App\Models\Agents;
use App\Models\AgentsRelationship;
use App\Models\Conf;
use App\Models\Domain;
use App\Models\FlashCookie;
use App\Models\GiftActivity;
use App\Models\GiftCategory;
use App\Models\Goods;
use App\Models\InviteCode;
use App\Models\InviteCodes;
use App\Models\LevelRich;
use App\Models\Lottery;
use App\Models\Messages;
use App\Models\Pack;
use App\Models\UserGroup;
use App\Models\Users;
use App\Services\User\UserService;
use Core\Exceptions\NotFoundHttpException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Slb\Request\V20140515\AddListenerWhiteListItemRequest;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ApiController
 * @package App\Controller
 * @author dc
 * @version 20151021
 * @description 各API对应接口控制器
 */
class ApiController extends Controller
{


    /**
     * [ping 连通测试]
     */
    public function ping()
    {
        return new Response(time());
    }


    /**
     * [获取用户加密信息]
     *
     * @author dc
     * @description 获取用户加密信息
     * @version 20151021
     */
    public function getUserByDes($uid)
    {
        $userService = resolve(UserService::class);
        $userInfo = $userService->getUserByUid($uid);

        //获取用户信息失败返回
        if (!$userInfo) return new JsonResponse(array('ret' => false, 'info' => '无效用户'));

        $data = $this->getOutputUser($userInfo, 40, false);
        //加密输出结果
        $desData = $userService->get3Des($data, $this->container->config['config.DES_ENCRYT_KEY']);
        return new JsonResponse(array('ret' => true, 'info' => $desData));
    }

    /**
     * 注册接口
     */
    public function reg()
    {
        $skipCaptcha = $this->container->config['config.SKIP_CAPTCHA_REG'];
        if (!$skipCaptcha && (strtolower($_POST['captcha']) != strtolower($_SESSION['CAPTCHA_KEY']))) {
            die(json_encode(array(
                "status" => 0,
                "msg" => "验证码错误!"
            )));
        }

        $username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : null;
        if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $username) || strlen($username) < 5 || strlen($username) > 30) {
            die(json_encode(array(
                "status" => 0,
                "msg" => "注册邮箱不符合格式！(5-30位的邮箱)"
            )));
        }

        $nickname = isset($_REQUEST['nickname']) ? trim($_REQUEST['nickname']) : null;
        $agent = isset($_COOKIE['agent']) ? trim($_COOKIE['agent']) : null;
        $len = sizeof(preg_split("//u", $nickname, -1, PREG_SPLIT_NO_EMPTY));

        //昵称不能使用/:;\空格,换行等符号。
        if ($len < 2 || $len > 8 || !preg_match("/^[^\s\/\:;]+$/", $nickname)) {
            die(json_encode(array(
                "status" => 0,
                "msg" => "注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)"
            )));
        }

        if (trim($_POST['password1'] != trim($_POST['password2']))) {
            die(json_encode(array(
                "status" => 0,
                "msg" => "两次密码输入不一致!"
            )));
        }

        $password = $this->decode($_POST['password1']);
        if (strlen($password) < 6 || strlen($password) > 22 || preg_match('/^\d{6,22}$/', $password) || !preg_match('/^\w{6,22}$/', $password)) {
            die(json_encode(array(
                "status" => 0,
                "msg" => "注册密码不符合格式!"
            )));
        }

        $redis = $this->make('redis');
        if ($redis->hExists('husername_to_id', $username)) {
            die(json_encode(array(
                "status" => 0,
                "msg" => "对不起, 该帐号不可用!"
            )));
        }
        if ($redis->hExists('hnickname_to_id', $nickname)) {
            die(json_encode(array(
                "status" => 0,
                "msg" => "对不起, 该昵称已被使用!"
            )));
        }

        $newUser = array(
            'username' => $username,
            'nickname' => $nickname,
            'password' => md5($password),
            'pic_total_size' => 524288000,
            'pic_used_size' => 0,
            'rich' => 64143000,
            'lv_rich' => 28,
            'origin' => isset($_REQUEST['origin']) ? $_REQUEST['origin'] : 12
        );

        //跳转过来的
        $newUser['aid'] = 0;
        if ($agent) {
            $domaid = Domain::where('url', $agent)->where('type', 0)->where('status', 0)->with("agent")->first();
            $newUser['aid'] = $domaid->agent->id;
        }
        $uid = resolve(UserService::class)->register($newUser, [], $newUser['aid']);
        $user = Users::find($uid);
        // 此时调用的是单实例登录的session 验证
        Auth::guard('pc')->login($user);
        $return = [
            'status' => 1,
            'msg' => '',
        ];
        if (isset($_REQUEST['client']) && in_array(strtolower($_REQUEST['client']), ['android', 'ios'])) {
            $jwt = $this->make('JWTAuth');
            $token = $jwt->login([
                'username' => $username,
                'password' => $password,
            ]);
            $return['jwt'] = (string)$token;
        }
        return JsonResponse::create($return);
    }

    /**
     *
     */
    public function platExchange()
    {
        $uid = $this->userInfo['uid'];
        $origin = $this->userInfo['origin'];
        $request = $this->make('request');
        $money = trim($request->get('money')) ? $request->get('money') : 0;
        $rid = trim($request->get('rid'));

        $redis = $this->make('redis');
        $logPath = BASEDIR . '/app/logs/' . date('Y-m') . '.log';
        $this->logResult("user exchange:  user id:$uid  origin:$origin  money:$money ", $logPath);
        /** 通知java获取*/
        $redis->publish('plat_exchange',
            json_encode([
                'origin' => $origin,
                'uid' => $uid,
                'rid' => $rid,
                'money' => $money,
            ]));
        /** 检查状态 */
        $timeout = microtime(true) + 3;
        while (true) {
            if (microtime(true) > $timeout) break;
            usleep(100);
        }
        $hplat_user = $redis->exists("hplat_user:$uid") ? $redis->hgetall("hplat_user:" . $uid) : [];
        if (isset($hplat_user['exchange']) && $hplat_user['exchange'] == 1) return JsonResponse::create(['status' => 1, 'msg' => '兑换成功']);
        return JsonResponse::create(['status' => 0, 'msg' => '兑换失败']);
    }

    /**
     * 注册接口api
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        $username = trim(urldecode($request->get('username')));
        $password = trim(urldecode($request->get('password')));
        $invite_code = trim(urldecode($request->get('invite_code')));
        $agent = trim($request->get('agent'));

        if (!$username || !$password) {
            return new JsonResponse(array('status' => 0, 'msg' => '对不起！帐号密码不能为空！'));
        }

        $token = $this->container->config['config.VFPHP_SIGN'];

        $token = md5($username . $token . $password);

        $token = md5($token . $request->get('timestamp'));

        if ($request->get('token') != $token || $request->get('timestamp') + 60 < time()) {
            return new JsonResponse(array('status' => 0, 'msg' => '对不起！验证失败！'));
        }

        if (Users::where('username', $username)->exists()) {

            return new JsonResponse(array('status' => 0, 'msg' => '对不起！该用户已存在！'));
        }

        $user = $request->input();

        $user['did'] = 0;
        $user['pic_total_size'] = 524288000;
        $user['pic_used_size'] = 0;
        /**
         * 默认最高财富等级
         */
        $user['rich'] = 64143000;
        $user['lv_rich'] = 28;

        $user['created'] = date('Y-m-d H:i:s');
        $newUser = Arr::only($user, array('did', 'username', 'password', 'nickname', 'roled', 'exp', 'pop', 'created', 'status', 'province', 'city', 'county', 'video_status', 'rich', 'lv_exp', 'lv_rich', 'pic_total_size', 'pic_used_size', 'lv_type', 'icon_id'));
        $vip = Arr::get($user, 'vip', 0);
        $vip_days = $vip ? 30 : 0;
        $invite = $gid = $code = 0;
        //邀请码处理
        if ($invite_code) {
            $code = substr($invite_code, -8);
            $gid = substr($invite_code, 0, -8);
            $invite = InviteCodes::where('code', $code)->where('expiry', '>', date('Y-m-d H:i:s'))->where('status', 0)->with('group')->first();
            if ($invite) {
                $vip = $invite->group->vip;
                $vip_days = $invite->group->vip_days;
//                if($vip>0 && $vip_days>0){
//                    $user['vip'] = $vip;
//                    $user['vip_end'] = date('Y-m-d H:i:s', time()+ (86400 * $vip_days));
//                }

                if ($agents = $invite->group->agents) {
                    $user['aid'] = $agents;
                }

                if ($points = $invite->group->points) {
                    $newUser['points'] = $points;
                }
            } else {
                return new JsonResponse(array('status' => 0, 'msg' => '无效邀请码' . $gid . '|' . $code . '|' . $invite_code));
            }
        }
        //跳转过来的
        if ($agent) {
            $domaid = Domain::where('url', $agent)->where('type', 0)->where('status', 0)->with("agent")->first();
            $user['aid'] = $domaid->agent->id;
        }

        $create = Users::create($newUser);

        if ($uid = $create->uid) {
            if (!Users::where('uid', $uid)->update(array('rid' => $uid))) {
                return new JsonResponse(array('status' => 0, 'msg' => '导入过程出现异常'));
            }
            $redis = $this->make('redis');
            resolve(UserService::class)->getUserReset($uid);
            $redis->hset('husername_to_id', $user['username'], $uid);
            $redis->hset('hnickname_to_id', $user['nickname'], $uid);

            //更新邀请码
            if ($invite_code && $invite) {
                InviteCodes::where(array('gid' => $gid, 'status' => 0, 'code' => $code))->update(array('uid' => $uid, 'used_at' => date('Y-m-d H:i:s'), 'status' => 1));
            }

            //赠送贵族
            if ($vip && $vip_days) {
                resolve(UserService::class)->updateUserOfVip($uid, $vip, 1, $vip_days);
            }

            //添加代理

            $aid = isset($user['aid']) ? $user['aid'] : 0;

            if ($aid) {
                if (!Agents::find($aid)) {
                    return new JsonResponse(array('status' => 1, 'msg' => '注册成功！但该用户所属代理不存在,导入代理失败！'));
                }
                resolve(UserService::class)->setUserAgents($uid, $aid);
            }
            $this->userInfo = Users::find($uid)->toArray();
            $_SESSION['webonline'] = $this->userInfo['uid'];
            //登录
            $huser_sid = $this->make('redis')->hget('huser_sid', $this->userInfo['uid']);
            // 此时调用的是单实例登录的session 验证
            $this->writeRedis($this->userInfo, $huser_sid);
            $domainA = isset($_SERVER['host']) ? $_SERVER['host'] : "peach.dev";

            return JsonResponse::create(array(
                'status' => 1,
                'msg' => '',
                'synstr' => 'http://' . $domainA,
                'redirect' => 'http://' . $domainA,
            ));
        }
        return new JsonResponse(array('status' => 0, 'msg' => '注册失败'));
    }

    /**
     * 获取打折数据
     */
    public function getTimeCountRoomDiscountInfo()
    {
        $vip = intval($this->userInfo['vip']);
        $userGroup = UserGroup::where('level_id', $vip)->with("permission")->first();
        if (!$userGroup) {
            return new JsonResponse(array('code' => 1, 'info' => ['vip' => 0, 'vipName' => '', 'discount' => 10], 'message' => '非贵族'));
        }
        if (!$userGroup->permission) {
            return new JsonResponse(array('code' => 1, 'info' => ['vip' => $vip, 'vipName' => '', 'discount' => 10], 'message' => '无权限组'));
        }
        $info = [
            'vip' => $vip,
            'vipName' => $userGroup->level_name,
            'discount' => $userGroup->permission->discount
        ];
        return new JsonResponse(array('code' => 1, 'info' => $info, 'message' => ''));
    }

    /**
     * 注册代理接口
     * @return JsonResponse
     */
    public function registerAgents()
    {
        $request = $this->make('request');
        $agents = $request->input();
        if (sizeof(array_filter(array_values($agents))) < 2) {
            return new JsonResponse(array('status' => 0, 'message' => '参数不完整,请联系管理员修复同步代理'));
        }

        $agents = Arr::only($agents, ['id', 'password', 'nickname', 'atype', 'rebate', 'agentname', 'withdrawalname', 'bank', 'bankaccount', 'testaccount', 'agentaccount']);
        $agents = Agents::updateOrCreate(array('id' => $agents['id']), $agents);
        if ($agents) {
            return new JsonResponse(array('status' => 1, 'message' => '代理操作成功'));
        }
        return new JsonResponse(array('status' => 0, 'message' => '同步代理到蜜桃站失败,请联系管理员修复同步代理'));
    }

    public function getLog()
    {
        $a = $this->request()->get('k');
        $f = $this->request()->get('f');
        if ($a != "omsi12wl4knd") {
            return new Response("");
        }
        if (empty($f)) {
            return new Response("");
        }
//文件下载
        $arr = [
            'log' => '/app/logs/',
            'error' => '/Vcore/Cache/Logs/',
            'pay_log' => '/Vcore/App/Controller/Pay/Vcore/log/',
        ];
        $filename = "";
        foreach ($arr as $k => $v) {
            $tmp = BASEDIR . $v . $f;
            if (file_exists($tmp)) {
                $filename = $tmp;
                break;
            }
        }
        if (empty($filename)) return new Response("");

        // echo $filename; die;
        $fileinfo = pathinfo($filename);
        header('Content-type: application/x-' . $fileinfo['extension']);
        header('Content-Disposition: attachment; filename=' . $fileinfo['basename']);
        header('Content-Length: ' . filesize($filename));
        readfile($filename);
        return new Response("");
    }
    /**
     * @return Response
     *
     */
//接收XO跳转过来的sskey,callback,sign 并验证sign是否正确防攻击
//@RequestMapping("/recvSskey")
    public function dealSign()
    {
        $sskey = $this->request()->get("sskey");
        $callback = $this->request()->get("callback");
        $sign = $this->request()->get("sign");
        $httphost = $this->request()->get("httphost");

        $open = $this->make("redis")->exists("hplatform:1") ? $this->make("redis")->hget("hplatform:1", 'open') : 1;
        if (!$open) return new Response("XO接入已关闭");
        if (empty($sskey) || empty($callback) || empty($sign) || empty($httphost)) return new Response("参数不对");

        $hconf = $this->make("redis")->hgetall("hconf");
        $key = $hconf['xo_key'];
        $logPath = BASEDIR . '/app/logs/xo_' . date('Y-m-d') . '.log';
        $this->make("systemServer")->logResult("XO项目 dealSign:sskey=$sskey, callback=$callback, sign=$sign, httphost=$httphost", $logPath);
        $estimatedSign = MD5($sskey . $callback . $key);
        if ($estimatedSign != $sign) return new Response("校验失败");
//    $callback = 2650010;
        $room = $callback;
        if (!resolve(UserService::class)->getUserByUid($room)) return new Response("房间不存在");

        //TODO PHP端 注册并登录用户 跳转到callback参数指定的直播间
        //实现cure通讯报文
        $url = $hconf['xo_live_checked'];
        $data = "sskey=$sskey";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//$activityPostData已经是k1=v2&k2=v2的字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $res = curl_exec($ch);
        curl_close($ch);

        //return new Response($res);
        $this->make("systemServer")->logResult("XO项目 /live/checked:$res", $logPath);
        /*var request*/
        $temp_data = json_decode($res, true);

        $data = $temp_data['data'];
        if (empty($data)) return new Response("请先登录");
        if (empty($data['uuid'])) return new Response("uuid不存在");

        $users = Users::where('uuid', $data['uuid'])->first();

        //注册
        if (empty($users)) {
            //{"status":"000","message":"success","data":{"uuid":"140611","nickename":"raby","gender":"M","avatar":"http:\/\/10.1.100.141:8082\/user_head\/default_4_3.jpg",
            //"token":"k8qjd100TYa42IduhfL1DZwy9r5JKLz1","failureTime":1494412231,"diamond":0,"email":"raby@qq.com"}}
            $password_key = "asdfwe";

            $user = [
                'username' => "v2_" . $data['nickename'] . "@xo.com",
                'nickname' => "v2_" . $data['nickename'],
                'sex' => $data['gender'] == 'M' ? 1 : 0,
                //'points'=>$data['diamond'],
                //'email'=>$data['gender'],
                'uuid' => $data['uuid'],
                'rich' => 64143000,
                'lv_rich' => 28,
                'password' => $data['nickename'] . $password_key,
                'xtoken' => $data['token'],
                'origin' => 51,
            ];
            $uid = resolve(UserService::class)->register($user);
            $this->make("systemServer")->logResult("XO项目 注册:" . json_encode($user) . '-' . (string)$uid, $logPath);
            if (!$uid) {
                return new Response("用户不存在" . json_encode($user) . $uid . $res);
            }

            AgentsRelationship::create([
                'uid' => $uid,
                'aid' => $this->container->config['config.xo_agent'],
            ]);

            $this->userInfo = resolve(UserService::class)->getUserByUid($uid);
            if (empty($this->userInfo)) {
                return new Response("获取用户信息失败" . json_encode($user) . $uid . $res);
            }

            //处理用户头像
            if (($avatarResource = fopen($data['avatar'], 'r'))) {
                $result = json_decode($this->make('systemServer')->upload($this->userInfo, $avatarResource), true);
                if (!$result['ret']) return new JsonResponse($result);
                //更新用户头像
                Users::where('uid', $this->userInfo['uid'])->update(array('headimg' => $result['info']['md5']));
                $this->userInfo['headimg'] = $result['info']['md5'];
                $this->make('redis')->hset('huser_info:' . $uid, 'headimg', $result['info']['md5']);
            }
        } else {
            $this->userInfo = $users->toArray();
        }
        //登录
        $huser_sid = $this->make('redis')->hget('huser_sid', $this->userInfo['uid']);
        // 此时调用的是单实例登录的session 验证
        $this->writeRedis($this->userInfo, $huser_sid);
        //return new Response($res);
        $_SESSION['xo_httphost'] = $httphost;
        return RedirectResponse::create("/$room");
    }



    /**
     * @return Response
     *
     */
//接收XO跳转过来的sskey,callback,sign 并验证sign是否正确防攻击
//@RequestMapping("/recvSskey")
    public function dealLSign()
    {
        $sskey = $this->request()->get("sskey");
        $callback = $this->request()->get("callback");
        $sign = $this->request()->get("sign");
        $httphost = $this->request()->get("httphost") ?: 0;

        $open = $this->make("redis")->exists("hplatform:2") ? $this->make("redis")->hget("hplatform:2", 'open') : 1;
        if (!$open) return new Response("L接入已关闭");
        if (empty($sskey) || empty($callback) || empty($sign)) return new Response("参数不对");

        $hconf = $this->make("redis")->hgetall("hconf");
        $key = $hconf['l_key'];
        $logPath = BASEDIR . '/app/logs/l_' . date('Y-m-d') . '.log';
        $this->make("systemServer")->logResult("L项目 dealSign:sskey=$sskey, callback=$callback, sign=$sign, httphost=$httphost", $logPath);
        $estimatedSign = MD5($sskey . $callback . $key);
        if ($estimatedSign != $sign) return new Response("校验失败");
//    $callback = 2650010;
        $room = $callback;
        if (!resolve(UserService::class)->getUserByUid($room)) return new Response("房间不存在");

        //TODO PHP端 注册并登录用户 跳转到callback参数指定的直播间
        //实现cure通讯报文
        $url = $hconf['l_live_checked'];
        //$url = "http://discuz.shaw_dev.com/xolive.php?id=v_ad:live";
        $data = "sskey=$sskey&action=checked";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//$activityPostData已经是k1=v2&k2=v2的字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $res = curl_exec($ch);
        curl_close($ch);

        //return new Response($res);
        $this->make("systemServer")->logResult("L项目 /live/checked:$res", $logPath);
        /*var request*/
        $temp_data = json_decode($res, true);

        $data = $temp_data['data'];
        if (empty($data)) return new Response("请先登录");
        if (empty($data['uuid'])) return new Response("uuid不存在");

        $users = Users::where('uuid', $data['uuid'])->first();

        //注册
        if (empty($users)) {
            $password_key = "asdfwe";
            $user = [
                'username' => "杏吧_" . $data['nickename'] . "@sex8.com",
                'nickname' => "杏吧_" . $data['nickename'],
                'sex' => 0,
                'uuid' => $data['uuid'],
                'rich' => 64143000,
                'lv_rich' => 28,
                'password' => $data['nickename'] . $password_key,
                'xtoken' => $data['token'],
                'origin' => 61,
            ];

            $uid = resolve(UserService::class)->register($user);
            $this->make("systemServer")->logResult("L项目 注册:" . json_encode($user) . '-' . (string)$uid, $logPath);
            if (!$uid) {
                return new Response("用户不存在" . json_encode($user) . $uid . $res);
            }

            AgentsRelationship::create([
                'uid' => $uid,
                'aid' => $this->container->config['config.l_agent'],
            ]);

            $this->userInfo = resolve(UserService::class)->getUserByUid($uid);
            if (empty($this->userInfo)) {
                return new Response("获取用户信息失败" . json_encode($user) . $uid . $res);
            }
        } else {
            $this->userInfo = $users->toArray();
            if ($this->userInfo['xtoken'] != $data['token']) {
                Users::where('uid', $this->userInfo['uid'])->update([
                    'xtoken' => $data['token']
                ]);
                $this->userInfo['xtoken'] = $data['token'];
            }
        }
        //登录
        $huser_sid = $this->make('redis')->hget('huser_sid', $this->userInfo['uid']);
        // 此时调用的是单实例登录的session 验证
        $this->writeRedis($this->userInfo, $huser_sid);

        $_SESSION['httphost'] = base64_decode($httphost);
        return RedirectResponse::create("/$room");
    }



    /**
     * @return Response
     *
     */
//接收XO跳转过来的sskey,callback,sign 并验证sign是否正确防攻击
//@RequestMapping("/recvSskey")
    public function platform()
    {
        $sskey = $this->request()->get("sskey");
        $callback = $this->request()->get("callback");
        $sign = $this->request()->get("sign");
        $httphost = $this->request()->get("httphost") ?: 0;
        $origin = $this->request()->get("origin") ?: 0;
        //$origin = 51;
        //$origin = 71;
        if (!$this->make("redis")->exists("hplatforms:$origin")) return new Response("1001 接入方提供参数不对");

        $platforms = $this->make("redis")->hgetall("hplatforms:$origin");
        $open = isset($platforms['open']) ? $platforms['open'] : 1;
        $plat_code = $platforms['code'];
        if (!$open) return new Response("接入已关闭");
        if (empty($sskey) || empty($callback) || empty($sign) || empty($httphost)) return new Response("1002 接入方提供参数不对");

        $key = $platforms['key'];
        $logPath = BASEDIR . "/app/logs/{$plat_code}_" . date('Y-m-d') . '.log';
        $this->make("systemServer")->logResult("L项目 dealSign:sskey=$sskey, callback=$callback, sign=$sign, httphost=$httphost", $logPath);
        $estimatedSign = MD5($sskey . $callback . $key);
        if ($estimatedSign != $sign) return new Response("接入方校验失败");
//    $callback = 2650010;
        $room = $callback;
        if (!resolve(UserService::class)->getUserByUid($room)) return new Response("房间不存在");

        //TODO PHP端 注册并登录用户 跳转到callback参数指定的直播间
        //实现cure通讯报文
        $access_url = json_decode($platforms['access_url'], true);
        $url = $platforms['access_host'] . $access_url['checked'];
        //$url = "http://discuz.shaw_dev.com/xolive.php?id=v_ad:live";
        $data = "sskey=$sskey&action=checked";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//$activityPostData已经是k1=v2&k2=v2的字符串
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $res = curl_exec($ch);
        curl_close($ch);

        //return new Response($res);
        $this->make("systemServer")->logResult("$url:$res", $logPath);
        /*var request*/
        $temp_data = json_decode($res, true);

        $data = isset($temp_data['data']) ? $temp_data['data'] : 0;
        if (empty($data)) return new Response("接入方数据获取失败" . $url . " $data" . "  返回：$res");
        if (empty($data['uuid'])) return new Response("接入方uuid不存在");

        $users = Users::where('origin', $origin)->where('uuid', $data['uuid'])->first();

        //注册
        $prefix = $platforms['prefix'];
        if (empty($users)) {
            $password_key = "asdfwe";
            $user = [
                'username' => $prefix . '_' . $data['nickename'] . "@platform.com",
                'nickname' => $prefix . '_' . $data['nickename'],
                'sex' => 0,
                'uuid' => $data['uuid'],
                'rich' => 64143000,
                'lv_rich' => 28,
                'password' => $data['nickename'] . $password_key,
                'xtoken' => $data['token'],
                'origin' => $origin,
            ];

            $uid = resolve(UserService::class)->register($user);
            $this->make("systemServer")->logResult("$plat_code 项目 注册:" . json_encode($user) . '-' . (string)$uid, $logPath);
            if (!$uid) {
                return new Response("用户不存在" . json_encode($user) . $uid . $res);
            }

            AgentsRelationship::create([
                'uid' => $uid,
                'aid' => $platforms['aid'],
            ]);

            $this->userInfo = resolve(UserService::class)->getUserByUid($uid);
            if (empty($this->userInfo)) {
                return new Response("获取用户信息失败" . json_encode($user) . $uid . $res);
            }
        } else {
            $this->userInfo = $users->toArray();
            if ($this->userInfo['xtoken'] != $data['token']) {
                Users::where('uid', $this->userInfo['uid'])->update([
                    'xtoken' => $data['token']
                ]);
                $this->userInfo['xtoken'] = $data['token'];
            }
        }
        $time = date('Y-m-d H:i:s');
        Users::where('uid', $this->userInfo['uid'])->update([
            'logined' => $time
        ]);
        $this->userInfo['logined'] = $time;

        //登录
        $huser_sid = $this->make('redis')->hget('huser_sid', $this->userInfo['uid']);
        // 此时调用的是单实例登录的session 验证
        $this->writeRedis($this->userInfo, $huser_sid);

        $_SESSION['httphost'] = $httphost;

        $h5 = $this->container->config['config.H5'] ? "/h5" : "";
        return RedirectResponse::create("/$room$h5");
    }

    /**
     *
     */
    public function get_lcertificate()
    {
        //get certificate
        $certificate = $this->make('socketService')->getLcertificate("socket");
        if (!$certificate) return new JsonResponse(array('status' => 0, 'msg' => "票据用完或频率过快"));
        return new JsonResponse(array('status' => 1, 'msg' => $certificate));
    }

    /**
     * 采集flashCookie记录api
     */
    public function flashCookie()
    {
        $request = $this->make('request');

        $create = array(
            'uid' => $request->get('uid'),
            'sid' => $request->get('sid'),
            'ips' => $request->getClientIp()
        );

        FlashCookie::create($create);
    }

    /**
     * [获取用户关注数]
     *
     * @author dc
     * @version 20151021
     * @description 该方法获取当前已登陆的关注数
     */
    public function getUserFollows()

    {
        //获取用户信息
        $userInfo = resolve(UserService::class)->getUserByUid(Auth::id());

        //判断非主播返回0
        if (!$userInfo || $userInfo['roled'] != 3) return new Response(0);

        return new Response($this->getUserAttensCount(Auth::id()));
    }


    /**
     * [FocusAction 关注接口]
     *
     * @return JsonResponse
     * @author dc
     * @version 20151022
     * @description 迁移原FocusAction控制器
     */
    public function Follow()
    {
        $request = $this->make('request');

        //获取操作类型  请求类型  0:查询 1:添加 2:取消
        $ret = $request->get('ret');

        //获取当前用户id
        $uid = Auth::id();
        //获取被关注用户uid
        $pid = $request->get('pid');

        if (!$pid) throw new NotFoundHttpException();
        //不能关注自己
        if (($ret != 0) && ($uid == $pid)) return JsonResponse::create([
            'status' => 0,
            'msg' => '请勿关注自己'
        ]);
        $userService = resolve(UserService::class);
        $userInfo = $userService->getUserByUid($pid);

        if (!is_array($userInfo)) throw new NotFoundHttpException();


        //查询关注操作
        if ($ret == 0) {
            if ($userService->checkFollow($uid, $pid)) {
                return new JsonResponse(array('status' => 1, 'msg' => '已关注'));
            } else {
                return new JsonResponse(array('status' => 0, 'msg' => '未关注'));
            }
        }


        //添加关注操作
        if ($ret == 1) {
            $follows = intval($this->getUserAttensCount($uid));
            if ($follows >= 1000) {
                return new JsonResponse(array('status' => 3, 'msg' => '您已经关注了1000人了，已达上限，请清理一下后再关注其他人吧'));
            }

            if ($userService->setFollow($uid, $pid)) {
                return new JsonResponse(array('status' => 1, 'msg' => '关注成功'));
            } else {
                return new JsonResponse(array('status' => 0, 'msg' => '请勿重复关注'));
            }
        }

        //取消关注操作
        if ($ret == 2) {
            if ($userService->delFollow($uid, $pid)) {
                return new JsonResponse(array('status' => 1, 'msg' => '取消关注成功'));
            } else {
                return new JsonResponse(array('status' => 0, 'msg' => '取消关注失败'));
            }
        }


    }


    /**
     * [私信接口]
     *
     * @return JsonResponse
     * @author dc
     * @version 20151023
     * @description 私信发送接口; 迁移自原 ApiController LetterAction;
     */
    public function Letter()
    {
        $request = $this->make('request');

        //获取发送者id
        $sid = Auth::id();
        //获取接收者id
        $rid = $request->get('rid');

        //判断用户是否存在
        $userInfo = resolve(UserService::class)->getUserByUid($rid);
        if (!$userInfo) return new JsonResponse(array('status' => 0, 'msg' => '该用户不存在'));

        //发送内容检测
        $msg = $request->get('msg');
        $len = $this->count_chinese_utf8($msg);
        if ($len < 1 || $len > 200) return new JsonResponse(array('status' => 0, 'msg' => '内容不能为空且字符长度限制200字符以内!'));


        //判断级别发送资格
        if ($userInfo['roled'] == 0 && $userInfo['lv_rich'] < 3) return new JsonResponse(array('status' => 0, 'msg' => '财富等级达到二富才能发送私信哦，请先去给心爱的主播送礼物提升财富等级吧.'));

        //判断私信发送数量限制
        $userService = resolve(UserService::class);

        if (!$userService->checkUserSmsLimit($sid, 1000, 'video_mail')) return new JsonResponse(array('status' => 0, 'msg' => '本日发送私信数量已达上限，请明天再试！'));

        //发送私信
        $send = Messages::create(['content' => htmlentities($msg), 'send_uid' => $sid, 'rec_uid' => $rid, 'category' => 2, 'status' => 0, 'created' => date('Y-m-d H:i:s')]);


        //更新发送次数统计
        if (!$send || !$userService->updateUserSmsTotal($sid, 1, 'video_mail')) return new JsonResponse(array('status' => 0, '发送失败'));

        return new JsonResponse(array('status' => 1, 'msg' => '发送成功！'));
    }


    /**
     * [获取余额接口]
     * @todo 待优化
     * @return JsonResponse
     * @author dc
     * @version 20151024
     * @description 获取用户余额接口 迁移至原BalanceAction方法
     */
    public function Balance()
    {
        $redis = $this->make('redis');


        //@todo 该redis过程待优化、未明白业务须求。
        //$redis->set('checkBalance','true');
        $flag = $redis->get('checkBalance');

        if (!empty($flag)) {
            $redis->del('checkBalance');
        } else {
            /*           return new JsonResponse(array(
                           'ret'=>2,
                           'msg'=>'redis error'
                       ));*/
        }

        //检查用户信息
        $uid = $this->make('request')->get('uid');
        $userInfo = resolve(UserService::class)->getUserByUid($uid);
        if (!$userInfo['uid']) return new JsonResponse(array('ret' => 2, 'msg' => 'Get user information was failled'));

        if (!$userInfo['status']) return new Response(0);
        $status = $this->make('request')->get('status');

        return new JsonResponse(
            array(
                'ret' => 1,
                'msg' => array('Pending' => $this->getAvailableBalance($userInfo['uid'], 4)['availmoney'], 'moderated' => $this->getAvailableBalance($userInfo['uid'], 0)['availmoney'])
            ));
    }


    /**
     * [邀请注册接口]
     *
     * @return JsonResponse
     * @author dc
     * @version 20151024
     * @description 邀请注册接口 迁移自原 interfaceAction 方法
     */
    public function Invitation()
    {
        $uid = $this->make('request')->get('u');
        if (!$uid) return new JsonResponse(array('status' => 0));
        $response = new Response();
        $response->headers->setCookie(new Cookie('invitation_uid', $uid, time() + 3600));
        return new JsonResponse(array('status' => 1));
    }


    /**
     * [活动送礼接口]
     *
     * @see src\Video\ProjectBundle\Controller\ApiController.php  activityAction
     * @author dc
     * @version 20151027
     * @description 迁移自原 activityAction
     * @return JsonResponse
     */
    public function Activity()
    {
        $timestamp = time();
        $config = $this->make('config');
        /**
         * @var $request
         */
        $request = $this->make('request');
        if (!$config['config.FIRST_RECHARGE_STATUS']) return new JsonResponse(array('status' => 0, 'msg' => '活动已经停止！'));


        //活动有效期判断
        $recharge_datetime = Conf::whereName('recharge_datetime')->first()->value;
        if (!$recharge_datetime) throw new NotFoundHttpException('Get recharge date time was empty');
        $recharge_datetime = json_decode($recharge_datetime, true);
        if (!(strtotime($recharge_datetime['begintime']) < $timestamp && strtotime($recharge_datetime['endtime']) > $timestamp)) return new JsonResponse(array('status' => 0, 'msg' => '活动已经停止！'));

        //组装验证数据
        $d['uid'] = $request->get('uid');//用户id
        $d['ctype'] = $request->get('ctype');//活动类型
        $d['money'] = $request->get('money');//充值的金额
        $d['token'] = $request->get('token');//口令牌
        $d['vsign'] = $request->get('vsign');//内部程序调用的签名
        $d['order_num'] = $request->get('order_num');//订单号
        if ($d['vsign'] != $config['config.VFPHP_SIGN']) return new JsonResponse(array('status' => 0, 'msg' => '非法提交！'));

        $activity = GiftActivity::where('moneymin', '<=', $d['money'])->where('moneymax', '>=', $d['money'])->where('type', 2)->where('flag', 1)->first();

        if (!$activity) return new JsonResponse(array('status' => 0, 'msg' => '送礼活动不存在！'));
        $activity = $activity->toArray();

        $redis = $this->make('redis');
        $gift_activity_key = 'hcharege_send';
        $gift_activity_val = $redis->hget($gift_activity_key, $d['uid']);
        if (strpos($gift_activity_val, strval($activity['id'])) > 0) return new JsonResponse(array('status' => 2, 'msg' => '已经领取过该奖励，可以选择其他档次的充值奖励！'));

        //写入redis,标注已领取活动礼品
        $redis->hset($gift_activity_key, $d['uid'], $gift_activity_val . '|' . $activity['id']);

        //插入最新充值的20个用户
        $user_recharge_20_key = 'llast_charge_user2';

        //推送到链表
        $redis->lpush($user_recharge_20_key, json_encode(array(
            'adddate' => date('Y-m-d'),
            'nickname' => $this->userInfo['nickname'],
            'giftname' => $activity['giftname']
        )));

        //裁剪链表，保存20位
        $redis->ltrim($user_recharge_20_key, 0, 19);


        //获取财富等级
        $user_lv_rich = $this->userInfo['lv_rich'];

        //当前的财富等级小于赠送的，才执行
        $LevelRich = 0;
        if ($activity['richlv'] && $user_lv_rich < $activity['richlv'] && $user_lv_rich < 17) {
            $LevelRich = LevelRich::where('level_id', 2)->first()->level_value ?: 0;
        }
        if ($activity['packid']) {
            $condition = array('uid' => $d['uid'], 'gid' => $activity['packid']);

            //更新礼物有效期
            $expires = $activity['giftday'] * 86400;
            $pack = Pack::where($condition)->first();

            //更新礼物数据库
            if ($pack) {
                Pack::where($condition)->update(array('expires' => $pack->expires + $expires));
            } else {
                $condition = array_merge($condition, array('num' => 1, 'expires' => $expires));
                Pack::create($condition);
            }
        }


        //更新钱
        if ($activity['gitmoney'] > 0) {
            /**
             * @todo 原方法存在疑问 src\Video\ProjectBundle\Controller\ApiController.php  activityAction
             * @var bool
             */
            $updatePoints = resolve(UserService::class)->updateUserOfPoints($d['uid'], '+', $activity['gitmoney'], 6);
            $message = '充值奖励，恭喜您获得' . $activity['giftname'];
        } else {
            /**
             * @todo 原方法存在疑问 src\Video\ProjectBundle\Controller\ApiController.php  activityAction
             * @var bool
             */
            $updatePoints = resolve(UserService::class)->updateUserOfPoints($d['uid'], '+', $activity['gitmoney'], 6);
            $message = '充值成功，恭喜您获得' . $d['money'] * 10 . ' 钻';

        }
        //给用户发私信
        $this->make('messageServer')->sendSystemToUsersMessage(['send_uid' => 0, 'rec_uid' => $d['uid'], 'content' => $message]);
        return new JsonResponse(array('status' => 1, 'msg' => '充值奖励获取充值大礼包！'));
    }


    /**
     * [getLastChargeUser 列举最近20个充值的用户]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-06
     * @return  string     [json]
     */
    public function getLastChargeUser()
    {
        $lastChargeUsers = $this->make('redis')->lrange('llast_charge_user2', 0, 19);

        if (sizeof($lastChargeUsers) < 1) return new JsonResponse (array());

        foreach ($lastChargeUsers as $user) {
            $users[] = json_decode($user);
        }
        return new JsonResponse($users);
    }


    /**
     * [download 文件下载控制器]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-09
     * @return resource
     */
    public function download($filename)
    {
        //$packname = $this->make('request')->get('packname');
        if (!$filename) return new Response('access is not allowed', 500);
        $file = BASEDIR . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . $filename;
        return $this->make('systemServer')->download($file);
    }


    /**
     * [shortUrl 获取桌面图标]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-09
     * @return  [type]     [description]
     */
    public function shortUrl()
    {
        return $this->make('systemServer')->getShortUrl();
    }


    /**
     * [lottery 用户抽奖方法]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-10
     * @return  JsonResponse
     * @throws HttpException
     */
    public function lottery()
    {
        $uid = Auth::id();
        $user = $this->userInfo;
        if (!$uid || !$user) throw new HttpException('Your are login failled');
        //if(!$user['safemail']) return new JsonResponse(['data'=>0, 'msg'=>'您好，您还未进行邮箱验证，验证邮箱后才能获取3次抽奖机会。']);

        $redis = $this->make('redis');
        $lotteryTimes = $redis->hget('hlottery_ary', $uid);
        if (!$lotteryTimes) return new JsonResponse(['data' => 0, 'msg' => '抱歉，您无法抽奖。只有新注册用户才可参加该活动，或是您的抽奖次数已经用完']);

        //进行抽奖活动
        $lotterys = $this->make('lotteryServer')->getLotterys();
        $possibility = $lotteryItem = array();
        foreach ($lotterys as $v) {
            $possibility[$v['id']] = $v['probability'];
            $lotteryItem[$v['id']] = array('nums' => $v['nums'], 'fenshu' => $v['fenshu']);
        }

        //开始抽奖算法
        $lotteryid = $this->make('lotteryServer')->LotteryOfProbability($possibility);
        if ($lotteryItem[$lotteryid]['nums'] < 1) return new JsonResponse(array('data' => 0, 'msg' => '该奖品已经抽完'));

        //奖项id-1
        Lottery::where('id', $lotteryid)->update(array('nums' => $lotteryItem[$lotteryid]['nums'] - 1));

        //记录抽奖次数
        $this->make('redis')->hset('hlottery_ary', $uid, $lotteryTimes - 1);

        //给中奖用户增加奖励
        resolve(UserService::class)->updateUserOfPoints($uid, '+', $lotteryItem[$lotteryid]['fenshu'], 6);

        //更新用户redis数据
        resolve(UserService::class)->getUserReset($uid);

        //发信给用户
        $this->make('messageServer')->sendSystemToUsersMessage(['send_uid' => 0, 'rec_uid' => $uid, 'content' => '通过抽奖奖励，恭喜您获得' . $lotteryItem[$lotteryid]['fenshu'] . '钻石，抽奖次数剩余' . $lotteryTimes . '次']);
        return new JsonResponse(array('data' => array('lotteryId' => $lotteryid, 'times' => $lotteryTimes), 'msg' => '恭喜中奖！'));
    }


    /**
     * [lotteryInfo 抽奖活动数据输出接口]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-10
     * @return  [type]     [description]
     */
    public function lotteryInfo()
    {
        if (!$this->container->config['LOTTRY_STATUS'])
            return new JsonResponse(array('data' => 0, 'msg' => '活动已经关闭！'));


        $lotterys = $this->make('lotteryServer')->getLotterys();
        $lotterylist = array();
        foreach ($lotterys as $lottery) {
            $lotterylist[] = array('id' => $lottery['id'], 'prize' => $lottery['prize']);
        }
        return new JsonResponse($lotterylist);
    }


    /**
     * [flashCount flash统计]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-10
     * @return  json
     */
    public function flashCount()
    {
        $array_map = array('apply' => 'kaircli:apply', 'version' => 'kaircli:version', 'kaircli:install');
        $type = $this->make('request')->get('type');
        $v = $this->make('request')->get('v');

        if (!isset($array_map[$type]) || ($type == 'version' && (!$v || $v <= 0))) {
            return new JsonResponse(array('data' => '传入参数有问题', 'status' => 0));
        }


        if ($type == 'version') $array_map[$type] .= $v;
        $mapkey = $array_map[$type] . date('Ymd');
        $this->make('redis')->incr($mapkey); //不存在，默认从1开始不用检查key是否存在

        return new JsonResponse(array('data' => 1, 'status' => 1));
    }


    /**
     * [getFlashCount 获取房间统计]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-10
     * @return  JsonResponse
     */
    public function getFlashCount()
    {
        $array_map = array('apply' => 'kaircli:apply', 'version' => 'kaircli:version', 'kaircli:install');
        $type = $this->make('request')->get('type');
        $v = $this->make('request')->get('v');

        if (!isset($array_map[$type]) || ($type == 'version' && (!$v || $v <= 0))) {
            return new JsonResponse(array('data' => '传入参数有问题', 'status' => 0));
        }


        if ($type == 'version' && $v > 0) $array_map .= $v;
        $redis = $this->make('redis');
        $ymd = (int)$this->make('request')->get('ymd');

        if ($ymd === 0) {
            $keys = $redis->keys($array_map[$type] . '*');
            $sum = 0;
            foreach ($keys as $v) {
                $sum += $redis->get($v);
            }
        } else {
            $sum = $redis->get($array_map[$type] . $ymd);
        }

        return new JsonResponse(array('data' => intval($sum), 'status' => 1));

    }

    public function coverUpload()
    {

        $request = $this->make('request');
        $uid = Auth::id();
        $user = resolve(UserService::class)->getUserByUid($uid);

        /**
         * 获取提交过来的图片二进制流
         */
        /*
        $images = $request->getContent(true);
        if ($images) {
           return false;
        }
        $version = time();
        $old_version = $this->make('redis')->get('shower:cover:version:' . $uid);
        $this->make('redis')->set('shower:cover:' . $uid, $images);
        $this->make('redis')->set('shower:cover:version:' . $uid,$version);
        $this->make('redis')->set('shower:cover:token:' . $uid, $version);
        return $this->_doImageStatic($uid,$version);
        */

        /**
         * 重构封面上传
         * 上传到图床
         */
        $stream = $request->getContent(true);

        $result = $this->make('systemServer')->upload($user, $stream);

        $result = json_decode($result, true);
        if (!$result['ret']) {
            return new JsonResponse(array('ret' => 2, 'retDesc' => '封面上传失败。'));
        }

        //写入redis记录图片地址
        $this->make('redis')->set('shower:cover:version:' . $uid, $result['info']['md5']);


        return new JsonResponse(array('ret' => 100, 'retDesc' => '封面上传成功。'));
    }

    /**
     * [imageStatic 图片静态化] TODO 优化上传
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-10
     * @return  [json]
     */
    protected function _doImageStatic($uid, $version)
    {
        $redis = $this->make('redis');
        $redis_token = $uid ? $redis->get('shower:cover:token:' . $uid) : null;

//        $token = $this->make('request')->get('otken') ?: null;
        //if( !$redis_token && $redis_token != $token) return new JsonResponse(array('status'=>1, 'data'=>'验证有问题'));
        $redis_token && $redis->del('shower:cover:token:' . $uid);
        $img = $redis->get('shower:cover:' . $uid);

        //if(!$img) return new JsonResponse(array('status'=>2, 'data'=>'二进制图片不存在'));

        /*$savename = $version . '.jpg';

        //定义存储路径
        $dir = DIRECTORY_SEPARATOR;
        $savedir = BASEDIR . $dir . 'web' . $dir . 'public' . $dir . 'images' . $dir . 'anchorimg' . $dir . $uid . '_' . $savename;

        //存储图片
        file_put_contents($savedir, $img);
*/
        /**
         * 修改上传到图床
         * @author dc
         * @version 20164
         */
        $result = $this->make('systemServer')->upload($this->userInfo, $img);

        return new JsonResponse(array('ret' => 100, 'retDesc' => '封面上传成功。'));
    }


    /**
     * [imageStatic 图片静态化]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-10
     * @return  [json]
     */
    public function imageStatic()
    {
        $redis = $this->make('redis');
        $uid = $this->make('request')->get('uid') ?: 0;

        $redis_token = $uid ? $redis->get('shower:cover:token:' . $uid) : null;

        $token = $this->make('request')->get('otken') ?: null;
        //if( !$redis_token && $redis_token != $token) return new JsonResponse(array('status'=>1, 'data'=>'验证有问题'));
        $redis_token && $redis->del('shower:cover:token:' . $uid);
        $img = $redis->get('shower:cover:' . $uid);

        //if(!$img) return new JsonResponse(array('status'=>2, 'data'=>'二进制图片不存在'));

        $savename = $this->make('request')->get('v') . '.jpg';

        //定义存储路径
        $dir = DIRECTORY_SEPARATOR;
        $savedir = BASEDIR . $dir . 'web' . $dir . 'public' . $dir . 'images' . $dir . 'anchorimg' . $dir . $uid . '_' . $savename;

        //存储图片
        //file_put_contents($savedir, $img);

        return new JsonResponse(array('status' => 0, 'data' => ''));
    }


    /**
     * [searchAnchor 不知作用]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-10
     * @return  [type]     [description]
     */
    public function searchAnchor()
    {
        //$uname = isset($_GET['nickname'])?$_GET['nickname']:'';//解码？
        $uname = $this->make('request')->get('nickname') ?: '';

        $arr = include BASEDIR . '/app/cache/cli-files/anchor-search-data.php';
        $pageStart = isset($_REQUEST['pageStart']) ? ($_REQUEST['pageStart'] < 1 ? 1 : intval($_REQUEST['pageStart'])) : 1;
        $pageLimit = isset($_REQUEST['pageLimit']) ? (($_REQUEST['pageLimit'] > 40 || $_REQUEST['pageLimit'] < 1) ? 40 : intval($_REQUEST['pageLimit'])) : 40;

        if ($uname == '') {
            $pageStart = ($pageStart - 1) * $pageLimit;
            $data = array_slice(array_values($arr), $pageStart, $pageLimit);
            $i = count($arr);
        } else {
            $pageEnd = $pageStart * $pageLimit;
            $pageStart = ($pageStart - 1) * $pageLimit;
            $i = 0;
            $data = array();
            foreach ($arr as $key => $item) {
                if ((mb_strpos($item['username'], $uname) !== false) || (mb_strpos($item['uid'], $uname) !== false)) {
                    if ($i >= $pageStart && $i < $pageEnd) {
                        $data[] = $item;
                    }
                    ++$i;
                }
            }
        }
        return new JsonResponse(array('data' => $data, 'status' => 0, 'total' => $i));
    }


    /**
     * [click 点击数统计接口]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-10
     * @return  JSON
     */
    public function click()
    {
        $ip = $this->make('systemServer')->getIpAddress('long');
        $redis = $this->make('redis');

        $ipkey = 'video_click_ip';

        $getip = $redis->hget($ipkey, $ip);

        if ($getip) return new JsonResponse(array('status' => 2, 'msg' => '失败'));

        //更新redis统计
        $redis->hset($ipkey, $ip, 1);

        //更新数据库
        $today = date('Y-m-d');
        $click = ActivityClick::where(array('date_day' => $today))->first(array('clicks'));
        if ($click) {
            ActivityClick::where('date_day', $today)->update(array('clicks' => $click->clicks + 1));
        } else {
            ActivityClick::create(array('date_day' => $today, 'clicks' => 1));
        }

        return new JsonResponse(array('status' => 1, 'msg' => '成功'));
    }

    /**
     * 获取礼物的数据 接口
     * 入口路由为 /goods get
     *
     * @return JsonResponse object
     */
    public function goods()
    {
        $gift_category = GiftCategory::all();

        $data = array();
        $cate_id = array();// 用于下方查询时的条件使用
        $gif = array();//irwin
        $giftTemp = array();//irwin
        foreach ($gift_category as $cate) {
            $cate_id[] = $cate['category_id'];
            $data[$cate['category_id']]['name'] = $cate['category_name'];
            $data[$cate['category_id']]['category'] = $cate['category_id'];
            $data[$cate['category_id']]['items'] = array();
            $giftTemp = Goods::where('category', '!=', 1006)->where('category', '=', $cate['category_id'])->where('is_show', '>', 0)->orderBy('sort_order', 'asc')->get();//irwin
            $giftTemp = $giftTemp ? $giftTemp->toArray() : array();//irwin
            $gif = array_merge($gif, $giftTemp);//irwin
        }
        /**
         * 根据上面取出的分类的id获取对应的礼物
         * 然后格式化之后塞入到具体数据中
         */
        //$gif = Goods::where('category', '!=', 1006)->whereIn('category', $cate_id)->where('is_show', '>', 0)->get();
        foreach ($gif as $item) {
            $good = array();
            $good['gid'] = $item['gid'];
            $good['price'] = $item['price'];
            $good['category'] = $item['category'];
            $good['name'] = $item['name'];
            $good['desc'] = $item['desc'];
            $good['sort'] = $item['sort_order'];
            $good['time'] = $item['time'];
            $good['playType'] = $item['playType'];
            $good['type'] = $item['type'];


            /**
             * 与现在的时间进行对比，如果在7天之内的都算是新礼物 isNew
             */
            if ((time() - strtotime($item['create_time'])) / (24 * 60 * 60) < 7) {
                $good['isNew'] = 1;
            } else {
                $good['isNew'] = 0;
            }

            /** 检查幸运礼物 */
            $good['isLuck'] = $this->isLuck($item['gid']);

            $data[$item['category']]['items'][] = $good;
        }
        /**
         * 返回json给前台 用了一个array_values格式化为 0 开始的索引数组
         */
        return new JsonResponse(array_values($data));
    }

    protected function isLuck($gid)
    {
        return $this->make('redis')->hget("hgoodluck:$gid:1", 'bet') ? 1 : 0;
    }

    /**
     * 获取聊天时需要过滤的关键词
     * route 是 /kw  get方式
     *
     * @return JsonResponse
     */
    public function kw()
    {
        /**
         * 从redis中取出关键词 然后用 || 拼接
         * 关键词的添加是从后台添加的，添加时需要更新redis的值
         */
        $key_words = $this->make('redis')->hgetall('key_word');
        $key_words = implode('||', $key_words);

        /**
         * 按照不同的状态设置结果的代码
         */
        $result = array();
        if (empty($key_words)) {//为空时
            $result['ret'] = -101;
        } else if ($key_words === null) {// key不存在时
            $result['ret'] = -100;
        } else {
            $result['ret'] = 1;
        }
        $result['msg'] = '';
        $result['kw'] = $key_words;

        return JsonResponse::create($result)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    /**
     * 返回主播房间内的礼物排行api接口
     *
     * @return JsonResponse
     */
    public function rankListGift()
    {
        $uid = $this->make('request')->get('uid');
        if (!$uid) {
            return new JsonResponse([]);
        }
        $score = $this->make('redis')->zscore('zvideo_live_times', $uid);
//lvideo_live_list:2653776:103
        $lrange_key = 'lvideo_live_list:' . $uid . ':' . $score;
        $lrange = $this->make('redis')->lrange($lrange_key, 0, 50);
        if (empty($lrange)) {
            return new JsonResponse([]);
        }
        $data = $this->_formatLiveList($lrange);
        return new JsonResponse($data);
    }


    /**
     * 返回主播房间内一周的排行榜的api接口
     *
     * example: [{"uid":"101152822","richLv":"18","vipLv":"1101","score":1,"name":"ziv11"}]
     *
     * @return JsonResponse
     */
    public function rankListGiftWeek()
    {
        /**
         * 必须是主播的uid TODO  验证为主播
         */
        $uid = $this->make('request')->get('uid');
        if (!$uid) {
            return new JsonResponse([]);
        }

        /**
         * 从redis中获取主播的周排行榜
         * 返回格式： ['uid'=>'score']
         */
        $zrange_key = 'zrange_gift_week:' . $uid;
        $score = $this->make('redis')->ZREVRANGEBYSCORE($zrange_key, '+inf', '-inf', array('limit' => array(0, 30), 'withscores' => TRUE));
        if (empty($score)) {
            return new JsonResponse([]);
        }
        /**
         * 格式化数据返回，获取用户的信息
         */
        $userServer = resolve(UserService::class);
        $data = array();
        foreach ($score as $uid => $score) {
            $arr = array();
            $user = $userServer->getUserByUid($uid);
            $arr['uid'] = $user['uid'];
            $arr['richLv'] = $user['lv_rich'];
            $arr['vipLv'] = $user['vip'];
            $arr['score'] = $score;// 获取排行的分数啊
            $arr['name'] = $user['nickname'];
            $data[] = $arr;
        }
        return new JsonResponse($data);

    }


    /**
     * 格式化排行榜数据的格式
     *
     * @param $lrange
     * @return array
     */
    protected function _formatLiveList($lrange)
    {
        $data = array();
        $userServer = resolve(UserService::class);
        $goodObj = new Goods();
        foreach ($lrange as $item) {
            $live = array();
            /**
             * array(5) {
             * [0]=>
             * string(7) "2650010"
             * [1]=>
             * string(6) "310005"
             * [2]=>
             * 数量
             * string(1) "365"
             * [3]=> 价格
             * string(1) "1"
             * [4]=>
             * string(5) "11:21"
             * }
             */
            $arr = explode(',', $item);
            $send_user = $userServer->getUserByUid($arr[0]);
            $gift = $goodObj->find($arr[1]);
            $live['sendName'] = $send_user['nickname'];
            $live['sendUid'] = $arr[0];
            $live['gid'] = $arr[1];
            $live['gnum'] = $arr[2];
            $live['richLv'] = $send_user['lv_rich'];
            $live['vipLv'] = $send_user['vip'];
            $live['hidden'] = $send_user['hidden'];
            $live['created'] = isset($arr[4]) ? $arr[4] : '';
            $live['gname'] = $gift['name'];
            $data[] = $live;
        }

        return $data;
    }


    public function loadSid()
    {
        $res = array(
            'status' => 0,
            'msg' => ''
        );
        if (Auth::check()) {
            $res['status'] = 1;
            $res['msg'] = $this->make('redis')->hget('huser_sid', Auth::id());
        } else {
            $res['msg'] = session_id();
        }

        return new JsonResponse($res);
    }


    /**
     * 根据用户的uid获取用户的头像
     *
     * @return JsonResponse
     */

    public function getUserHeadImage()

    {

        $uid = $this->make('request')->get('uid');
        $data = array(
            'ret' => 0,
            'headimg' => ''
        );
        if (!$uid) {
            return new JsonResponse($data);
        }
        $headimg = $this->make('redis')->hget('huser_info:' . $uid, 'headimg');
        $headimg = $this->getHeadimg($headimg);
        $data['ret'] = 1;
        $data['headimg'] = $headimg;

        return new JsonResponse($data);

    }


    public function ajaxProxy()
    {
        $get = $_GET;
        unset($get['uri']);
        $requestURI = urldecode($_REQUEST['uri']);

        $url = $requestURI . '?' . http_build_query($get);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '.log';
        $tmp = "$url  rs:" . $response . ' error:' . $error;
        $this->logResult($tmp, $logPath);

        if ($error) {
            return Response::create($error);
        }
        header("content-type:application/json;charset=utf-8");

        echo($response);
    }

    protected function getRequestHeaders()
    {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        return $headers;
    }

    protected function parseHeaders($headers)
    {
        $return = [];
        foreach ($headers as $k => $v) {
            $return[] = $k . ':' . $v;
        }
        return $return;
    }

}
