<?php

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Models\AgentsRelationship;
use App\Models\Conf;
use App\Models\Domain;
use App\Models\GiftCategory;
use App\Models\Goods;
use App\Models\LevelRich;
use App\Models\Messages;
use App\Models\Pack;
use App\Models\SiteConfig;
use App\Models\UserGroup;
use App\Models\Users;
use App\Services\Auth\JWTGuard;
use App\Services\Message\MessageService;
use App\Services\Safe\SafeService;
use App\Services\Site\Config;
use App\Services\System\SystemService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Mews\Captcha\Facades\Captcha;

/**
 * Class ApiController
 * @package     App\Controller
 * @author      dc
 * @version     20151021
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
     * @author      dc
     * @description 获取用户加密信息
     * @version     20151021
     */
    public function getUserByDes($uid)
    {
        $userService = resolve(UserService::class);
        $userInfo = $userService->getUserByUid($uid);

        //获取用户信息失败返回
        if (!$userInfo) return new JsonResponse(['status' => 0, 'msg' => '无效用户']);

        $data = $this->getOutputUser($userInfo, 40, false);
        //加密输出结果
        $desData = $userService->get3Des($data, $this->container->config['config.DES_ENCRYT_KEY']);
        return new JsonResponse(['status' => 1, 'data' => $desData]);
    }

    /**
     * 注册接口
     */
    public function reg(Request $request)
    {
        $skipCaptcha = SiteSer::config('skip_captcha_reg');

        if (!$skipCaptcha && !Captcha::check($request->get('captcha'))) {
            return JsonResponse::create([
                "status" => 0,
                "msg" => "验证码错误!",
            ]);
        }
        $username = $request->get('username');
        if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $username) || strlen($username) < 5 || strlen($username) > 30) {
            return JsonResponse::create([
                "status" => 0,
                "msg" => "注册邮箱不符合格式！(5-30位的邮箱)",
            ]);
        }
        $nickname = $request->get('nickname');
        $agent = $request->get('agent');
        $len = sizeof(preg_split("//u", $nickname, -1, PREG_SPLIT_NO_EMPTY));

        //昵称不能使用/:;\空格,换行等符号。
        if ($len < 2 || $len > 8 || !preg_match("/^[^\s\/\:;]+$/", $nickname)) {
            return JsonResponse::create([
                "status" => 0,
                "msg" => "注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)",
            ]);
        }

        if ($request->get('password1') != $request->get('password2')) {
            return JsonResponse::create([
                "status" => 0,
                "msg" => "两次密码输入不一致!",
            ]);
        }

        $password = $this->decode($request->get('password1'));
//        $password = $request->get('password1');
        if (strlen($password) < 6 || strlen($password) > 22 || preg_match('/^\d{6,22}$/', $password) || !preg_match('/^\w{6,22}$/', $password)) {
            return JsonResponse::create([
                "status" => 0,
                "msg" => "注册密码不符合格式!",
            ]);
        }

        $redis = resolve('redis');
        if ($redis->hExists('husername_to_id:'.SiteSer::siteId(), $username)) {
            return JsonResponse::create([
                "status" => 0,
                "msg" => "对不起, 该帐号不可用!",
            ]);
        }
        if ($redis->hExists('hnickname_to_id:'.SiteSer::siteId(), $nickname)) {
            return JsonResponse::create([
                "status" => 0,
                "msg" => "对不起, 该昵称已被使用!",
            ]);
        }

        $newUser = [
            'username' => $username,
            'nickname' => $nickname,
            'password' => md5($password),
            'pic_total_size' => 524288000,
            'pic_used_size' => 0,
            'rich' => 64143000,//TODO
            'lv_rich' => 28,//TODO
            'origin' => $request->get('origin', 12),
        ];

        //跳转过来的
        $newUser['aid'] = 0;
        if ($agent) {
            $domaid = Domain::where('url', $agent)->where('type', 0)->where('status', 0)->with("agent")->first();
            $newUser['aid'] = $domaid->agent->id;
        }
        $uid = resolve(UserService::class)->register($newUser, [], $newUser['aid']);
        if (!$uid){
            return JsonResponse::create(['status'=>0,'注册失败']);
        }
        $user = Users::find($uid);
        // 此时调用的是单实例登录的session 验证
        $guard = null;
        if ($request->route()->getName() === 'm_reg' || $request->has('client') && in_array(strtolower($request->get('client')), ['android', 'ios'])) {
            /** @var JWTGuard $guard */
            $guard = Auth::guard('mobile');
            $guard->login($user);
            $return['data'] = [
                'jwt' => (string)$guard->getToken(),
            ];
        } else {
            $guard = Auth::guard('pc');
            $guard->login($user);
            $return['data'] = [
                Session::getName() => Session::getId(),
            ];
        }
        return JsonResponse::create($return);
    }

    /**
     *
     */
    public function platExchange()
    {

        $uid = Auth::user()->uid;
        $origin = Auth::user()->origin;
        $request = $this->make('request');
        $money = trim($request->get('money')) ? $request->get('money') : 0;
        $rid = trim($request->get('rid'));

        $redis = $this->make('redis');

        Log::channel('daily')->info('user exchange', [" user id:$uid  origin:$origin  money:$money "]);

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
     * 获取打折数据
     */
    public function getTimeCountRoomDiscountInfo()
    {

        $vip = intval(Auth::user()->vip);
        $userGroup = UserGroup::where('level_id', $vip)->with("permission")->first();

        if (!$userGroup) {
            return new JsonResponse(['status' => 1, 'data' => ['vip' => 0, 'vipName' => '', 'discount' => 10], 'message' => '非贵族']);
        }
        if (!$userGroup->permission) {
            return new JsonResponse(['status' => 1, 'data' => ['vip' => $vip, 'vipName' => '', 'discount' => 10], 'message' => '无权限组']);
        }
        $info = [
            'vip' => $vip,
            'vipName' => $userGroup->level_name,
            'discount' => $userGroup->permission->discount,
        ];
        return new JsonResponse(['status' => 1, 'data' => $info, 'msg' => '获取成功']);
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
    public function platformGetUser(){
        return JsonResponse::create(['data'=>[
            'uuid'=>0 ? '888'.mt_rand(10000,90000) : 88876597,
            'nickename'=>0 ? 'test'.mt_rand(100,900) : "test767",
            'token'=>time(),
        ]]);
    }
    /**
     * @return Response
     *
     */
//接收XO跳转过来的sskey,callback,sign 并验证sign是否正确防攻击
//@RequestMapping("/recvSskey")
    public function platform(Request $request)
    {

        $attributes = [
            'sskey'    => 'sskey',
            'sign'   => 'sign',
            'callback'  => 'callback',
            'httphost'  => '地址',
        ];
        $validator = Validator::make($request->all(),[
            'sskey'=>'required',
            'sign'=>'required',
            'httphost'=>'required',
            'callback' => 'required|max:15|min:5',
        ],[
            'sskey'  => ':attribute不能为空',
            'sign'   => ':attribute不能为空',
            'callback'       => ':attribute长度（数值）不对',
            'httphost'  => ':attribute不能为空',
        ],$attributes);
        if ($validator->fails()) {
            return new Response($validator->errors()->all());         //显示所有错误组成的数组
            return new Response("1002 接入方提供参数不对");
        }

        $sskey = $request->get("sskey");
        $callback = $request->get("callback");
        $sign = $request->get("sign");
        $httphost = $request->get("httphost",0);
        $origin = $request->get("origin",0);
        if (!$this->make("redis")->exists("hplatforms:$origin")) return new Response("1001 接入方提供参数不对");

        $platforms = $this->make("redis")->hgetall("hplatforms:$origin");
        $open = isset($platforms['open']) ? $platforms['open'] : 1;
        $plat_code = $platforms['code'];
        if (!$open) return new Response("接入已关闭");
        //if (empty($sskey) || empty($callback) || empty($sign) || empty($httphost)) return new Response("1002 接入方提供参数不对");

        $key = $platforms['key'];
        Log::channel('plat')->info("$plat_code 项目 dealSign:sskey=$sskey, callback=$callback, sign=$sign, httphost=$httphost");
        $sign_data = [$sskey,$callback,$key];
        if (!$this->checkSign($sign_data,$sign) && config('app.debug') == false) return new Response("接入方校验失败");
//        if ($estimatedSign != $sign) return new Response("接入方校验失败");
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
        Log::channel('plat')->info("$plat_code $url:$res");
        /*var request*/
        $temp_data = json_decode($res, true);

        $data = isset($temp_data['data']) ? $temp_data['data'] : 0;
        if (empty($data)) return new Response("接入方数据获取失败" . $url . " $data" . "  返回：$res");
        if (empty($data['uuid'])) return new Response("接入方uuid不存在");


        //注册
        $prefix = $platforms['prefix'];
        $username = $prefix . '_' . $data['nickename'] . "@platform.com";
        $users =  UserSer::getUserByUsername($username);//Users::where('origin', $origin)->where('uuid', $data['uuid'])->first();
        $password_key = "asdfwe";
        $password = $data['nickename'] . $password_key;
        if (empty($users)) {
            $user = [
                'username' => $username,
                'nickname' => $prefix . '_' . $data['nickename'],
                'sex' => 0,
                'uuid' => $data['uuid'],
                'password' => $password,
                'xtoken' => $data['token'],
                'origin' => $origin,
            ];

            $uid = resolve(UserService::class)->register($user);
            Log::channel('plat')->info("$plat_code 项目 注册:" . json_encode($user) . '-' . (string)$uid);
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
            $this->userInfo = $users;
            if ($this->userInfo['xtoken'] != $data['token']) {
                Users::where('uid', $this->userInfo['uid'])->update([
                    'xtoken' => $data['token'],
                ]);
                $this->userInfo['xtoken'] = $data['token'];
            }
        }
        $time = date('Y-m-d H:i:s');
        Users::where('uid', $this->userInfo['uid'])->update([
            'logined' => $time,
        ]);
        $this->userInfo['logined'] = $time;
        Redis::hmset('huser_info:'.$this->userInfo['uid'],[
            'logined' => $time,
            'xtoken' => $this->userInfo['xtoken'],
        ]);

        // 此时调用的是单实例登录的session 验证
        if(Auth::guest()){
            if(!Auth::attempt([
                'username'=>$this->userInfo['username'],
                'password'=>$password,
            ])){
                return JsonResponse::create(['status' => 0,'data'=>$this->userInfo['username'].$password, 'msg' => '用户名密码错误']);
            };
        }

        Session::put('httphost',$httphost);

        $h5 = SiteSer::config('h5') ? "/h5" : "";
        //return RedirectResponse::create("/$room$h5");
        return JsonResponse::create([
            'data'=>[
                'httphost'=>$httphost,
                'h5'=>$h5,
            ],
        ]);
    }
    public function checkSign($sign_data,$expect_sign){
        return md5(implode('',$sign_data))==$expect_sign;
    }
    /**
     *
     */
    public function get_lcertificate()
    {
        //get certificate
        $certificate = resolve(SafeService::class)->getLcertificate("socket");
        if (!$certificate) return new JsonResponse(['status' => 0, 'msg' => "票据用完或频率过快"]);
        return ['status' => 1, 'data' => ['datalist' => $certificate], 'msg' => '获取成功'];
    }



    /**
     * [获取用户关注数]
     *
     * @author      dc
     * @version     20151021
     * @description 该方法获取当前已登陆的关注数
     */
    public function getUserFollows()

    {
        //获取用户信息
        $userInfo = Auth::user();

        //判断非主播返回0
        if (!$userInfo->isHost()) return JsonResponse::create([
            'status' => 0,
            'data' => ['num' => 0],
        ]);
        return JsonResponse::create([
            'status' => 1,
            'data' => ['num' => $this->getUserAttensCount(Auth::id())],
        ]);
    }


    /**
     * 关注接口
     */
    public function Follow(Request $request)
    {
        //获取操作类型  请求类型  0:查询 1:添加 2:取消
        $ret = $request->get('ret');
        //获取当前用户id
        $uid = Auth::id();
        //获取被关注用户uid
        $pid = $request->get('pid');
        if (!$pid) {
            return JsonResponse::create([
                'status' => 0,
                'msg' => '参数错误',
            ]);
        };
        //不能关注自己
        if (($ret != 0) && ($uid == $pid)) {
            return JsonResponse::create([
                'status' => 0,
                'msg' => '请勿关注自己',
            ]);
        }
        $userService = resolve(UserService::class);
        $userInfo = $userService->getUserByUid($pid);

        if (!$userInfo) {
            return JsonResponse::create([
                'status' => 0,
                'msg' => '用户不存在',
            ]);
        }

        //查询关注操作
        if ($ret == 0) {
            if ($userService->checkFollow($uid, $pid)) {
                return new JsonResponse(['status' => 1, 'msg' => '已关注']);
            } else {
                return new JsonResponse(['status' => 0, 'msg' => '未关注']);
            }
        }


        //添加关注操作
        if ($ret == 1) {
            $follows = intval($this->getUserAttensCount($uid));
            if ($follows >= 1000) {
                return new JsonResponse(['status' => 3, 'msg' => '您已经关注了1000人了，已达上限，请清理一下后再关注其他人吧']);
            }

            if ($userService->setFollow($uid, $pid)) {
                return new JsonResponse(['status' => 1, 'msg' => '关注成功']);
            } else {
                return new JsonResponse(['status' => 0, 'msg' => '请勿重复关注']);
            }
        }

        //取消关注操作
        if ($ret == 2) {
            if ($userService->delFollow($uid, $pid)) {
                return new JsonResponse(['status' => 1, 'msg' => '取消关注成功']);
            } else {
                return new JsonResponse(['status' => 0, 'msg' => '取消关注失败']);
            }
        }


    }


    /**
     * [私信接口]
     *
     * @return JsonResponse
     * @author      dc
     * @version     20151023
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
        if (!$userInfo) return new JsonResponse(['status' => 0, 'msg' => '该用户不存在']);

        //发送内容检测
        $msg = $request->get('msg');
        $len = $this->count_chinese_utf8($msg);
        if ($len < 1 || $len > 200) return new JsonResponse(['status' => 0, 'msg' => '内容不能为空且字符长度限制200字符以内!']);


        //判断级别发送资格
        if ($userInfo['roled'] == 0 && $userInfo['lv_rich'] < 3) return new JsonResponse(['status' => 0, 'msg' => '财富等级达到二富才能发送私信哦，请先去给心爱的主播送礼物提升财富等级吧.']);

        //判断私信发送数量限制
        $userService = resolve(UserService::class);

        if (!$userService->checkUserSmsLimit($sid, 1000, 'video_mail')) return new JsonResponse(['status' => 0, 'msg' => '本日发送私信数量已达上限，请明天再试！']);

        //发送私信
        $send = Messages::create(['content' => htmlentities($msg), 'send_uid' => $sid, 'rec_uid' => $rid, 'category' => 2, 'status' => 0, 'created' => date('Y-m-d H:i:s')]);


        //更新发送次数统计
        if (!$send || !$userService->updateUserSmsTotal($sid, 1, 'video_mail')) return new JsonResponse(['status' => 0, '发送失败']);

        return new JsonResponse(['status' => 1, 'msg' => '发送成功！']);
    }


    /**
     * [获取余额接口]
     * @todo        待优化
     * @return JsonResponse
     * @author      dc
     * @version     20151024
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
        if (!$userInfo['uid']) return new JsonResponse(['ret' => 2, 'msg' => 'Get user information was failled']);

        if (!$userInfo['status']) return new Response(0);
        $status = $this->make('request')->get('status');

        return new JsonResponse(
            [
                'status' => 1,
                'data' => ['Pending' => $this->getAvailableBalance($userInfo['uid'], 4)['availmoney'], 'moderated' => $this->getAvailableBalance($userInfo['uid'], 0)['availmoney']],
            ]);
    }


    /**
     * [邀请注册接口]
     *
     * @return JsonResponse
     * @author      dc
     * @version     20151024
     * @description 邀请注册接口 迁移自原 interfaceAction 方法
     */
    public function Invitation()
    {
        $uid = $this->make('request')->get('u');
        if (!$uid) return new JsonResponse(['status' => 0]);
        return JsonResponse::create(['status' => 1])->cookie(Cookie::make('invitation_uid', $uid, time() + 3600));
    }


    /**
     * [getLastChargeUser 列举最近20个充值的用户]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-06
     * @return  string     [json]
     */
    public function getLastChargeUser()
    {
        $lastChargeUsers = Redis::lrange('llast_charge_user2', 0, 19);

        if (sizeof($lastChargeUsers) < 1) return new JsonResponse (['status' => 0]);

        foreach ($lastChargeUsers as $user) {
            $users[] = json_decode($user);
        }
        return JsonResponse::create(['data' => $users]);
    }


    /**
     * [download 文件下载控制器]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-09
     * @return resource
     */
    public function download($filename)
    {
        //$packname = $this->make('request')->get('packname');
        if (!$filename) return new Response('access is not allowed', 500);
        $file = BASEDIR . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . $filename;
        return resolve(SystemService::class)->download($file);
    }


    /**
     * [shortUrl 获取桌面图标]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-09
     * @return  [type]     [description]
     */
    public function shortUrl()
    {
        return resolve(SystemService::class)->getShortUrl();
    }


    public function getDownRtmp_test()
    {
        $data=json_decode(file_get_contents(route('downrtmp')));
        $jsonFormat=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        $method = 'AES-128-CBC';
        $iv=base64_decode($data->iv);
        $key=SiteSer::config('downrtmp_key');
        $dataDecrypt=openssl_decrypt($data->data,$method,$key,0,$iv);
        $dataDecryptFormat=json_encode(json_decode($dataDecrypt),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        echo <<<EOT
<p>获取使用cdn主播中房间人数最少的</p>
<pre>$jsonFormat</pre>
<p>data解密后数据</p>
<pre>$dataDecryptFormat</pre>
EOT;

    }

    public function getDownRtmp()
    {
        /** @var \Redis $redis */
        $redis = $this->make('redis');
        $rids = $redis->hGetAll('hroom_ids');
        $minUserNo = PHP_INT_MAX;
        $minHost = [];
        foreach ($rids as $rid) {
            //过滤不在直播
            $ktv = $redis->hgetall("hvediosKtv:$rid");
            if (!$ktv['status']) continue;
            //跳过人数多的
            if ($minUserNo <= $ktv['total']) continue;
            //获取下播
            $port = $ktv['rtmp_port'] ?: '';
            $host = $ktv['rtmp_host'];
            $srtmp = $redis->sMembers('srtmp_server');
            $rtmp_up = '';
            //过滤无supVip线路直播
            foreach ($srtmp as $up) {
                if (preg_match(
                    "/$host:?$port(.*)@@superVIP/"
                    , $up
                )) {
                    $rtmp_up = explode('@@', $up)[0];
                    break;
                }
            }
//            $rtmp_up = empty($port) ? "rtmp://$host/proxypublish" : "rtmp://$host:$port/proxypublish";
            $rtmp_down = $redis->smembers("srtmp_user:$rtmp_up");
            //foreach ($rtmp_down as $k => $tmp_down) {
            //    if (strpos($tmp_down, 'superVIP') === false)
            //        unset($rtmp_down[$k]);
            //}
            if (empty($rtmp_down)) continue;
            $minUserNo = $ktv['total'];
            $sid = $redis->hget('hvedios_ktv_set:' . $rid, 'sid');
            $minHost['rid'] = $rid;
            $minHost['total'] = $ktv['total'];

            $rtmp_down = explode('@@', $rtmp_down[0]);
            switch($rtmp_down[1]){
                case 'superVIP:1':
                    $args = $this->getXingyunSign();
                    $minHost['rtmp'] = $rtmp_down['0'] . '/' . $sid . '?' . http_build_query($args);
                    break;
                case 'superVIP:3':
                    $args = $this->getBaiyunshanSign($rtmp_down['0'] . '/' . $sid);
                    $minHost['rtmp'] = $rtmp_down['0'] . '/' . $sid . '?' . http_build_query($args);
                    break;
                default:
                    $minHost['rtmp'] = $rtmp_down['0'] . '/' . $sid;
            }
        }

        Log::info("棋牌接口：".json_encode($minHost));
        //
        $method = 'AES-128-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $downRTMP['data'] = openssl_encrypt(json_encode($minHost, JSON_UNESCAPED_SLASHES), $method, (string)SiteSer::config('downrtmp_key'), 0, $iv);
        $downRTMP['iv'] = base64_encode($iv);
        return JsonResponse::create($downRTMP);
    }


    /**
     * 白云山
     * @param string $uri
     * @return array
     */
    protected function getBaiyunshanSign($uri = "")
    {
        $redis = $this->make('redis');
        $rtmp_cdn_key = $redis->hget('hrtmp_cdn:3', 'key');
        $down_expire_sec = $redis->hget('hrtmp_cdn:3', 'down_expire_sec');
        $time = dechex(time()+$down_expire_sec);
        $tmp_uri = parse_url($uri, PHP_URL_PATH);
        $uri = trim($tmp_uri);
        $k = hash('md5', $rtmp_cdn_key .$uri. $time);
        return [
            'sign' => $k,
            't' => $time
        ];
    }
    /**
     * 星云签名
     * @return array
     */
    protected function getXingyunSign()
    {
        $redis = $this->make('redis');
        $rtmp_cdn_key = $redis->hget('hrtmp_cdn:1', 'key');
        $time = time();
        $k = hash('md5', $rtmp_cdn_key . $time);
        return [
            'k' => $k,
            'time' => $time
        ];
    }

    /**
     * [flashCount flash统计]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-10
     */
    public function flashCount(Request $request)
    {
        $array_map = ['apply' => 'kaircli:apply', 'version' => 'kaircli:version', 'kaircli:install'];
        $type = $request->get('type');
        $v = $request->get('v');

        if (!isset($array_map[$type]) || ($type == 'version' && (!$v || $v <= 0))) {
            return new JsonResponse(['msg' => '传入参数有问题', 'status' => 0]);
        }


        if ($type == 'version') $array_map[$type] .= $v;
        $mapkey = $array_map[$type] . date('Ymd');
        Redis::incr($mapkey); //不存在，默认从1开始不用检查key是否存在

        return new JsonResponse(['data' => ['count' => 1], 'status' => 1]);
    }


    /**
     * [getFlashCount 获取房间统计]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-10
     * @return  JsonResponse
     */
    public function getFlashCount(Request $request)
    {
        $array_map = ['apply' => 'kaircli:apply', 'version' => 'kaircli:version', 'kaircli:install'];
        $type = $request->get('type');
        $v = $request->get('v');

        if (!isset($array_map[$type]) || ($type == 'version' && (!$v || $v <= 0))) {
            return new JsonResponse(['data' => '传入参数有问题', 'status' => 0]);
        }


        if ($type == 'version' && $v > 0) $array_map .= $v;
        $redis = resolve('redis');
        $ymd = (int)$request->get('ymd');

        if ($ymd === 0) {
            $keys = $redis->keys($array_map[$type] . '*');
            $sum = 0;
            foreach ($keys as $v) {
                $sum += $redis->get($v);
            }
        } else {
            $sum = $redis->get($array_map[$type] . $ymd);
        }

        return new JsonResponse(['data' => ['count'=>intval($sum)], 'status' => 1]);

    }

    public function coverUpload(Request $request)
    {
        $uid = Auth::id();
        $user = Auth::user();

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
        $result = resolve(SystemService::class)->upload($user->toArray(), $stream);
        if (isset($result['status']) && $result['status'] != 1) {
            return JsonResponse::create($result);
        }
        if (isset($result['ret']) && $result['ret'] === false) {
            return JsonResponse::create(['data' => $result]);
        }

        //写入redis记录图片地址
        $this->make('redis')->set('shower:cover:version:' . $uid, $result['info']['md5']);

        return JsonResponse::create(['msg' => '封面上传成功。']);
    }

    /**
     * [imageStatic 图片静态化] TODO 优化上传
     *
     * @author  dc <dc#wisdominfo.my>
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
         * @author  dc
         * @version 20164
         */
        $result = $this->make('systemServer')->upload($this->userInfo, $img);

        return new JsonResponse(['ret' => 100, 'retDesc' => '封面上传成功。']);
    }


    /**
     * [imageStatic 图片静态化]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-10
     */
    public function imageStatic(Request $request)
    {
        $redis = resolve('redis');
        $uid = $request->get('uid') ?: 0;

        $redis_token = $uid ? $redis->get('shower:cover:token:' . $uid) : null;

        $token = $request->get('otken') ?: null;
        //if( !$redis_token && $redis_token != $token) return new JsonResponse(array('status'=>1, 'data'=>'验证有问题'));
        $redis_token && $redis->del('shower:cover:token:' . $uid);
        $img = $redis->get('shower:cover:' . $uid);

        //if(!$img) return new JsonResponse(array('status'=>2, 'data'=>'二进制图片不存在'));

        $savename = $request->get('v') . '.jpg';

        //定义存储路径
        $dir = DIRECTORY_SEPARATOR;
        $savedir = BASEDIR . $dir . 'web' . $dir . 'public' . $dir . 'images' . $dir . 'anchorimg' . $dir . $uid . '_' . $savename;

        //存储图片
        //file_put_contents($savedir, $img);

        return new JsonResponse(['status' => 0, 'data' => '']);
    }


    /**
     * 搜索主播
     */
    public function searchAnchor(Request $request)
    {
        $uname = $request->get('nickname') ?: '';

        $arr = include Storage::path('cache/anchor-search-data.php');;
        $pageStart = isset($request['pageStart']) ? ($request['pageStart'] < 1 ? 1 : intval($request['pageStart'])) : 1;
        $pageLimit = isset($request['pageLimit']) ? (($request['pageLimit'] > 40 || $request['pageLimit'] < 1) ? 40 : intval($request['pageLimit'])) : 40;

        if ($uname == '') {
            $pageStart = ($pageStart - 1) * $pageLimit;
            $data = array_slice(array_values($arr), $pageStart, $pageLimit);
            $i = count($arr);
        } else {
            $pageEnd = $pageStart * $pageLimit;
            $pageStart = ($pageStart - 1) * $pageLimit;
            $i = 0;
            $data = [];
            foreach ($arr as $key => $item) {
                if ((mb_strpos($item['username'], $uname) !== false) || (mb_strpos($item['uid'], $uname) !== false)) {
                    if ($i >= $pageStart && $i < $pageEnd) {
                        $data[] = $item;
                    }
                    ++$i;
                }
            }
        }
        return JsonResponse::create(['data' => $data, 'status' => 1]);
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

        $data = [];
        $cate_id = [];// 用于下方查询时的条件使用
        $gif = [];//irwin
        $giftTemp = [];//irwin
        foreach ($gift_category as $cate) {
            $cate_id[] = $cate['category_id'];
            $data[$cate['category_id']]['name'] = $cate['category_name'];
            $data[$cate['category_id']]['category'] = $cate['category_id'];
            $data[$cate['category_id']]['items'] = [];
            $giftTemp = Goods::where('category', '!=', 1006)->where('category', '=', $cate['category_id'])->where('is_show', '>', 0)->orderBy('sort_order', 'asc')->get();//irwin
            $giftTemp = $giftTemp ? $giftTemp->toArray() : [];//irwin
            $gif = array_merge($gif, $giftTemp);//irwin
        }
        /**
         * 根据上面取出的分类的id获取对应的礼物
         * 然后格式化之后塞入到具体数据中
         */
        //$gif = Goods::where('category', '!=', 1006)->whereIn('category', $cate_id)->where('is_show', '>', 0)->get();
        foreach ($gif as $item) {
            $good = [];
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
        return new JsonResponse(['data'=>['list'=>array_values($data)]]);
    }

    protected function isLuck($gid)
    {
        return Redis::hget("hgoodluck:$gid:1", 'bet') ? 1 : 0;
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
        $result = [];
        if (empty($key_words)) {//为空时
            $result['status'] = 0;
        } else if ($key_words === null) {// key不存在时
            $result['status'] = 0;
        } else {
            $result['status'] = 1;
        }
        $result['msg'] = '获取成功';
        $result['data']['key_word'] = $key_words;

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
            return new JsonResponse(['data'=>'','status'=>0,'请输入会员id']);
        }
        $score = $this->make('redis')->zscore('zvideo_live_times', $uid);
//lvideo_live_list:2653776:103
        $lrange_key = 'lvideo_live_list:' . $uid . ':' . $score;
        $lrange = $this->make('redis')->lrange($lrange_key, 0, 50);
        if (empty($lrange)) {
            return new JsonResponse(['data'=>'','status'=>0]);
        }
        $data = $this->_formatLiveList($lrange);
        return new JsonResponse(['data'=>$data,'msg'=>'获取成功']);
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
           return new JsonResponse(['data'=>'','status'=>0,'msg'=>'请输入会员id']);
        }

        /**
         * 从redis中获取主播的周排行榜
         * 返回格式： ['uid'=>'score']
         */
        $zrange_key = 'zrange_gift_week:' . $uid;
        $score = $this->make('redis')->ZREVRANGEBYSCORE($zrange_key, '+inf', '-inf', ['limit' => ['offset'=>0,'count'=>30], 'withscores' => TRUE]);
        if (empty($score)) {
            return new JsonResponse(['data'=>'','status'=>0,'msg'=>'数据为空']);
        }
        /**
         * 格式化数据返回，获取用户的信息
         */
        $userServer = resolve(UserService::class);
        $data = [];
        foreach ($score as $uid => $score) {
            $arr = [];
            $user = $userServer->getUserByUid($uid);
            $arr['uid'] = $user['uid'];
            $arr['richLv'] = $user['lv_rich'];
            $arr['vipLv'] = $user['vip'];
            $arr['score'] = $score;// 获取排行的分数啊
            $arr['name'] = $user['nickname'];
            $data[] = $arr;
        }
        return new JsonResponse(['data'=>['list'=>$data],'msg'=>'获取成功']);

    }


    /**
     * 格式化排行榜数据的格式
     *
     * @param $lrange
     * @return array
     */
    protected function _formatLiveList($lrange)
    {
        $data = [];
        $userServer = resolve(UserService::class);
        $goodObj = new Goods();
        foreach ($lrange as $item) {
            $live = [];
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
        $res = [
            'status' => 0,
            'msg' => '获取失败',
        ];

        if (Auth::check()) {
            $res['status'] = 1;
            $res['data']['sid'] = $this->make('redis')->hget('huser_sid', Auth::id());
            $res['msg'] = '获取成功';
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
        $data = [
            'status' => 0,
            'data' => [
                'headimg' => '',
            ],
        ];
        if (!$uid) {
            return new JsonResponse($data);
        }
        $headimg = $this->make('redis')->hget('huser_info:' . $uid, 'headimg');
        $headimg = $this->getHeadimg($headimg);
        $data['status'] = 1;
        $data['data']['headimg'] = $headimg;

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

        Log::channel('daily')->info("ajaxProxy  :", ["$url  rs:" . $response . ' error:' . $error]);

        if ($error) {
            return Response::create($error);
        }
        header("content-type:application/json;charset=utf-8");

        echo($response);
    }

    protected function getRequestHeaders()
    {
        $headers = [];
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
