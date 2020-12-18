<?php
/**
 * @apiDefine Api 共用功能
 */
namespace App\Http\Controllers;

use App\Events\Login;
use App\Events\ShareUser;
use App\Models\UserLoginLog;
use App\Services\RedisCacheService;
use App\Services\ShareService;
use App\Services\GuardianService;
use App\Services\Api\ApiService;
use App\Services\Site\SiteService;
use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Models\AgentsRelationship;
use App\Models\Conf;
use App\Models\Domain;
use App\Models\GiftCategory;
use App\Models\Goods;
use App\Models\Keywords;
use App\Models\LevelRich;
use App\Models\Messages;
use App\Models\Pack;
use App\Models\SiteConfig;
use App\Models\UserGroup;
use App\Models\Users;
use App\Services\Auth\JWTGuard;
use App\Services\I18n\PhoneNumber;
use App\Services\Message\MessageService;
use App\Services\Safe\SafeService;
use App\Services\Site\Config;
use App\Services\Sms\SmsService;
use App\Services\System\SystemService;
use App\Services\User\RegService;
use App\Services\User\UserService;
use App\Services\UserAttrService;
use App\Traits\Commons;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Facades\Agent;
use Mews\Captcha\Facades\Captcha;
use App\Models\Agents;
use DB;


/**
 * Class ApiController
 * @package     App\Controller
 * @author      dc
 * @version     20151021
 * @description 各API对应接口控制器
 */
class ApiController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    use Commons;

    /**
     * [ping 连通测试]
     */
    public function ping()
    {
        $data = [
            'status' => 1,
            'data'   => [
                'code' => time()
            ],
            'msg'    => ''
        ];

        if (isset($_GET['h'])) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-',
                        ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            $data['headers'] = $headers;
        }

        return $data;
    }


    /**
     * [获取用户加密信息]
     *
     * @author      dc
     * @description 获取用户信息
     * @version     20151021
     */
    public function getUserByDes($uid)
    {
        $userService = resolve(UserService::class);
        $userInfo = $userService->getUserByUid($uid);

        //获取用户信息失败返回
        if (!$userInfo) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Api.getUserByDes.invalid_user')]);
        }

        $data = $this->getOutputUser($userInfo, 40, false);
        $keepKey = [
            'headimg',
            'nickname',
            'vip',
            'lv_rich',
            'sex',
            'age',
            'starname',
            'procity',
            'uid',
            'space_url',
            'roled',
            'attens'
        ];
        foreach ($data as $k => $v) {
            if (!in_array($k, $keepKey)) {
                unset($data[$k]);
            }
        }
        //加密输出结果
//        $desData = $userService->get3Des($data, app(SiteService::class)->config('DES_ENCRYT_KEY'));
        return new JsonResponse(['status' => 1, 'data' => $data, 'msg' => __('messages.successfully_obtained')]);
    }

    /**
     * @return static
     */
    public function getConf()
    {
        /* captcha v2 驗證 */
        $c = dechex(time()) . '.' . substr(md5($this->getIp()), 1, 6);

        $conf = collect((new Config(SiteSer::siteId()))->all())->only([
            'cdn_host',
            'api_host',
            'flash_version',
            'img_host',
            'in_limit_points',
            'in_limit_safemail',
            'publish_version',
            'one_to_more_max_duration',
            'one_to_more_max_point',
            'one_to_more_min_duration',
            'one_to_more_min_point',
            'one_to_more_change_line',
            'one_to_one_max_duration',
            'one_to_one_max_point',
            'one_to_one_min_duration',
            'one_to_one_min_point',
            'chat_fly_limit',
            'customer_service_url',
            'hqt_game_status',
            'hqt_marquee',
            'socket_quality',
        ])->put('c', $c)->all();

        return JsonResponse::create(['data' => $conf]);
    }

    /**
     * @return array
     */
    public function getPreConf()
    {
        return [
            "OPEN_WEB"          => "1",
            "IMG_HOST"          => "1",
            "PIC_CDN_STATIC"    => "1",
            "flash_version"     => "",
            "publish_version"   => "1",
            "in_limit_points"   => "1",
            "in_limit_safemail" => "1",
        ];
    }

    /**
     * @api {post} /reg 注册接口
     * @apiGroup Api
     * @apiName reg
     * @apiVersion 1.0.0
     *
     * @apiParam {String} [scode] 分享代碼
     * @apiParam (m) {Int} use_mobile 是否使用手機(0:否/1:是)
     * @apiParam (m) {String} cc 國碼
     * @apiParam (m) {String} mobile 手機
     * @apiParam (m) {String} code 手機驗證碼
     * @apiParam {String} captcha 驗證碼
     * @apiParam {String} username 使用者名稱(使用信箱)
     * @apiParam {String} nickname 匿稱
     * @apiParam {String} [agent] 代理網址
     * @apiParam {String} password1 密碼
     * @apiParam {String} password2 確認密碼
     * @apiParam {Int} origin 来源：11 ,12,21,22,31,32，第一位（1网页，2安卓，3IOS，4蜜情 5XO），第二位（1直播间，2前台，3后台）
     * @apiParam {String} [client] 手機系統(android/ios)
     * @apiParam {String} [scode] 分享碼
     * @apiParam {String} [locale] 語系(EX: zh)
     *
     * @apiParamExample {json} Request-Example:
     * /api/reg/6U90DC24
     *
     * {
    "use_mobile":1,
    "cc":"+886",
    "mobile":"0987654321",
    "code":"1234",
    "captcha":"1234",
    "username":"theman@xxx.com",
    "nickname":"火星人",
    "agent":"",
    "password1":"test123456",
    "password2":"test123456",
    "origin":22,
    "client":"android"
    "scode":"aaa",
    "locale":"en"
    }
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccessExample {json} (mobile)成功回應
     * {
    "data": {
    "jwt": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1ODUyMTUyNzIsImV4cCI6MTYxNjMxOTI3MiwidWlkIjo5NDkzNTQxLCJ1c2VybmFtZSI6InRoZW1hbkB4eHguY29tIn0.HCKmDzUAGpvPGVIdpzPlaMmvPd5azgzFhqDxvg7vfnI",
    "user": {
    "uid": 9493541,
    "did": null,
    "sid": null,
    "username": "theman@xxx.com",
    "password": "4035c9353d0273d9d38aed83a92107c2",
    "nickname": "火星人",
    "safemail": null,
    "cc_mobile": "+8860987654321",
    "sex": null,
    "exp": 0,
    "roled": 0,
    "description": null,
    "points": 0,
    "created": "2020-03-26 17:34:32",
    "logined": "2020-03-26 17:34:32",
    "rid": null,
    "rich": 0,
    "pop": 0,
    "status": 1,
    "vip": 0,
    "vip_end": null,
    "province": 0,
    "city": 0,
    "county": 0,
    "video_status": 0,
    "birthday": null,
    "headimg": null,
    "headimg_sagent": null,
    "lv_exp": 1,
    "lv_rich": 1,
    "pic_total_size": 524288000,
    "pic_used_size": 0,
    "lv_type": 1,
    "icon_id": 0,
    "isedit": 0,
    "hidden": 0,
    "transfer": 0,
    "trade_password": null,
    "last_play_date": null,
    "last_ip": null,
    "first_charge_time": null,
    "broadcast_type": 0,
    "show_timecost": 0,
    "rtmp_ip": null,
    "safemail_at": null,
    "uuid": null,
    "xtoken": null,
    "origin": 22,
    "p2p_password": "",
    "app_key": "",
    "pwd_change": 1,
    "cpwd_time": "2020-03-26 09:34:32",
    "site_id": 1,
    "update_at": null,
    "cover": "",
    "qrcode_image": "",
    "guard_id": 0,
    "guard_end": null,
    "locale": "en"
    }
    },
    "msg": "",
    "status": 1
    }
     */
    public function reg(Request $request)
    {
        $regService = resolve(RegService::class);
        $shareService = resolve(ShareService::class);
        $userAttrService = resolve(UserAttrService::class);
        $useMobile = $request->post('use_mobile', 0) == '1';
        $scode = $request->scode ?? null;

        $status = $regService->status();
        if ($status == RegService::STATUS_BLOCK) {
            $this->setStatus(0, __('messages.Api.reg.ip_block'));
            return $this->jsonOutput();
        }

        $site_id = SiteSer::siteId();
        $cc_mobile = '';
        if ($useMobile) {
            $cc = $request->post('cc', '');
            $mobile = $request->post('mobile', '');
            $code = $request->post('code', '');
            if (empty($cc) || empty($mobile) || empty($code)) {
                $this->setStatus(0, __('messages.Api.reg.invalid_request'));
                return $this->jsonOutput();
            }
            $mobile = PhoneNumber::formatMobile($cc, $mobile);

            $cc_mobile = $cc . $mobile;
            if (Users::where('cc_mobile', $cc_mobile)->exists()) {
                $this->setStatus(0, __('messages.Api.reg.mobile_is_used'));
                return $this->jsonOutput();
            }

            $result = SmsService::verify(SmsService::ACT_REG, $cc, $mobile, $code);
            if ($result !== true) {
                return $this->msg($result);
            }
        }

        $skipCaptcha = SiteSer::config('skip_captcha_reg');
        $needCaptcha = !$skipCaptcha && $status == RegService::STATUS_NEED_CAPTCHA;
        if (!$useMobile && $needCaptcha && !Captcha::check($request->get('captcha'))) {
            $this->setStatus(0, __('messages.Api.reg.captcha_error'));
            return $this->jsonOutput();
        }

        $username = $request->get('username');
        if (empty($username)) {
            $username = $regService->randomEmail();
        } else {
            if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/',
                    $username) || strlen($username) < 5 || strlen($username) > 30) {
                $this->setStatus(0, __('messages.Api.reg.username_wrong_format'));
                return $this->jsonOutput();
            }
        }

        $nickname = $request->get('nickname');
        if ($useMobile && empty($nickname)) {
            $nickname = $regService->randomNickname();
        }

        $agent = $request->get('agent');
        $len = sizeof(preg_split("//u", $nickname, -1, PREG_SPLIT_NO_EMPTY));

        //昵称不能使用/:;\空格,换行等符号。
        if ($len < 2 || $len > 11 || !preg_match("/^[^\s\/\:;]+$/", $nickname)) {
            $this->setStatus(0, __('messages.Api.reg.nickname_wrong_format'));
            return $this->jsonOutput();
        }

        // 关键字过滤
        if (!$regService->isWhitelist($nickname)) {
            $query = Keywords::where('btype', 2)->where('status', 0)->groupby('keyword')->get(['keyword'])->toArray();
            if (is_array($query)) {
                foreach ($query as $v) {
                    $v['keyword'] = addcslashes($v['keyword'], '.^$*+?()[]{}|\\');
                    if (preg_match("/{$v['keyword']}/i", $nickname)) {
                        $this->setStatus(0, __('messages.Api.reg.nickname_is_lawbreaking'));
                        return $this->jsonOutput();
                    }
                }
            }
        }

        $password2 = $request->get('password2');
        if (!empty($password2) && $request->get('password1') != $password2) {
            $this->setStatus(0, __('messages.Api.reg.password_is_not_the_same'));
            return $this->jsonOutput();
        }

        $password1 = $request->get('password1');
        if ($useMobile && empty($password1)) {
            $password = substr(md5($nickname . mt_rand(100, 999)), 0, 10);
        } else {
            $password = $this->decode($password1);
        }
        $passlen = strlen($password);
        if ($passlen < 6 || $passlen > 22 || preg_match('/^\d{6,22}$/', $password)) {
            $this->setStatus(0, __('messages.Api.reg.password_wrong_format'));
            return $this->jsonOutput();
        }

        if (Users::where('username', $username)->exists()) {
            $this->setStatus(0, __('messages.Api.reg.username_is_used'));
            return $this->jsonOutput();
        }
        if (Users::where('nickname', $nickname)->exists()) {
            $this->setStatus(0, __('messages.Api.reg.nickname_is_used'));
            return $this->jsonOutput();
        }

        /* 解碼分享碼 */
        if (!empty($scode)) {
            $shareCode = $shareService->decScode($scode);
            info('分享碼解碼結果: ' . $shareCode);
        }

        $newUser = [
            'username'       => $username,
            'nickname'       => $nickname,
            'cc_mobile'      => $cc_mobile,
            'password'       => md5($password),
            'pic_total_size' => 524288000,
            'pic_used_size'  => 0,
            'origin'         => $request->get('origin', 12),
        ];

        //跳转过来的
        $newUser['aid'] = 0;
//        if ($agent) {
//            $domaid = Domain::where('url', $agent)->where('type', 0)->where('status', 0)->with("agent")->first();
//            if ($domaid->agent->id) {
//                $newUser['aid'] = $domaid->agent->id;
//            }
//        }
        /* 如有推廣人則取得推廣人之aid */
//        if (!empty($shareUid)) {
//            $shareUser = Users::find($shareUid);
//            $newUser['aid'] = $shareUser->agentRel->aid;
//        }

        if ($shareService->isAgent($scode)) {
            $newUser['aid'] = $shareCode;
            $newUser['did'] = $shareCode;
        }

        if ($shareService->isUser($scode)) {
            $newUser['share_uid'] = $shareCode;
        }

        $uid = resolve(UserService::class)->register($newUser, [], $newUser['aid']);
        if (!$uid) {
            $this->setStatus(0, __('messages.Api.reg.nickname_repeat'));
            return $this->jsonOutput();
        }

        $user = Users::find($uid);
        $locale = $request->{locale} ?? 'zh';

        /* 註冊用戶語系 */
        $userAttrService->set($user->uid, 'locale', $locale);

        $this->checkAgent($uid);

        /* 新增用戶推廣清單資訊 */
        if ($shareService->isUser($scode)) {
            $shareId = $shareService->addUserShare(
                $uid,
                $shareCode,
                $newUser['aid'],
                '',
                $request->get('client'),
                $cc_mobile
            );

            /* 全民代理推廣事件 */
            if (!empty($cc_mobile) && !empty($shareCode) && !empty($user)) {
                event(new ShareUser($user));
            }
        }

        // 此时调用的是单实例登录的session 验证
        $guard = null;
        if ($request->route()->getName() === 'm_reg'
            || $request->has('client')
            && in_array(strtolower($request->get('client')), ['android', 'ios'])
        ) {
            /** @var JWTGuard $guard */
            $guard = Auth::guard('mobile');
            $guard->login($user);

            //添加是否写入sid判断
            $sidUser = resolve(RedisCacheService::class)->sid($uid);
            if (empty($sidUser)) {
                $this->setStatus(0, __('messages.Api.reg.redis_token_error'));
                return $this->jsonOutput();
            }

            $origin = $request->origin;

            $return['data'] = [
                'jwt'  => (string)$guard->getToken(),
                'user' => $user,
            ];

            $return['data']['user']['locale'] = $userAttrService->get($user->uid, 'locale');
        } else {
            if (empty($user)) {
                $this->setStatus(0, __('messages.Api.reg.please_login'));
                return $this->jsonOutput();
            }
            $guard = Auth::guard('pc');
            $guard->login($user);
            $origin = 11;
            $return['data'] = [
                Session::getName() => Session::getId(),
            ];
        }

        //註冊成功時紀錄IP
        app('events')->dispatch(new \App\Events\Login($user, false, $origin));

        return JsonResponse::create($return);
    }

    /**
     * 检查代理商注册，获取cookie的agent值，来关联代理商
     * @param $uid
     * @return bool|int
     */
    private function checkAgent($uid)
    {

        if (isset($_REQUEST['origin']) && $_REQUEST['origin'] >= 20 && $_REQUEST['origin'] <= 39) {
            //移动端，agent为id
            $aid = isset($_REQUEST['aid']) ? $_REQUEST['aid'] : (isset($_REQUEST['agents']) ? $_REQUEST['agents'] : null);
            if (!empty($aid)) {
                // $did = Domain::where('url', $aid)->first();
                $agentid = Agents::where('id', $aid)->first();
                if (!isset($agentid['id'])) {
                    return;
                }
                $agent = array(
                    'aid' => $agentid['id'],
                    'uid' => $uid
                );
                DB::table((new AgentsRelationship)->getTable())->insert($agent);
                return;
            }

        } else {
            if (isset($_COOKIE['agent'])) {
                $agenturl = $_COOKIE['agent'];
                $did = Domain::where('url', $agenturl)->first();
                $agentid = Agents::where('did', $did['id'])->first();

                if (empty($agentid)) {
                    return false;
                }
                $agent = array(
                    'aid' => $agentid['id'],
                    'uid' => $uid
                );

                if (!empty($did)) {
                    $this->doclick($did);
                }
                DB::table((new AgentsRelationship)->getTable())->insert($agent);
                return 0;
            }
        }

    }

    private function doclick($did)
    {//注册成功增加点击量
        DB::table((new Domain)->getTable())->where('id', $did['id'])->increment('click', 1);

    }

    /**
     * money 积分值或其他币值
     */
    public function platExchange()
    {
//        return JsonResponse::create(['status' => 0, 'msg' => '無法兌換']);

        $uid = Auth::user()->uid;
        $origin = Auth::user()->origin;
        $request = $this->make('request');
        $money = trim($request->get('money')) ? $request->get('money') : 0;
        $rid = trim($request->get('rid'));
        $site_id = 2;

        $redis = $this->make('redis');

        Log::channel('daily')->info('user exchange', [" user id:$uid  origin:$origin  money:$money "]);

        /** 通知java获取*/
        $redis->del("hplat_user:$uid");
        $redis->publish('plat_exchange',
            json_encode([
                'origin'  => $origin,
                'uid'     => $uid,
                'rid'     => $rid,
                'money'   => $money,
                'site_id' => $site_id,
            ]));
        /** 检查状态 */
        $timeout = microtime(true) + 10;
        while (true) {
            if ($redis->exists("hplat_user:$uid")) {
                break;
            }
            if (microtime(true) > $timeout) {
                break;
            }
            usleep(100);
        }
        $hplat_user = $redis->exists("hplat_user:$uid") ? $redis->hgetall("hplat_user:" . $uid) : [];
        if (isset($hplat_user['exchange'])) {
            if ($hplat_user['exchange'] == 1) {
                return JsonResponse::create(['status' => 1, 'msg' => __('messages.exchanged_successful')]);
            } elseif ($hplat_user['exchange'] == 2) {
                return JsonResponse::create(['status' => 0, 'msg' => __('messages.Api.platExchange.processing')]);
            } elseif ($hplat_user['exchange'] == 3) {
                return JsonResponse::create(['status' => 0, 'msg' => __('messages.Api.platExchange.Already_exist')]);
            } else {
                return JsonResponse::create(['status' => 0, 'msg' => __('messages.exchanged_failed')]);
            }
        } else {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.exchanged_failed')]);
        }

    }

    /**
     * 获取打折数据
     */
    public function getTimeCountRoomDiscountInfo()
    {

        $vip = intval(Auth::user()->vip);
        $userGroup = UserGroup::where('level_id', $vip)->with("permission")->first();

        if (!$userGroup) {
            return new JsonResponse([
                'status'  => 1,
                'data'    => ['vip' => 0, 'vipName' => '', 'discount' => 10],
                'message' => __('messages.Api.getTimeCountRoomDiscountInfo.not_vip'),
            ]);
        }
        if (!$userGroup->permission) {
            return new JsonResponse([
                'status'  => 1,
                'data'    => ['vip' => $vip, 'vipName' => '', 'discount' => 10],
                'message' => __('messages.Api.getTimeCountRoomDiscountInfo.permission_denied'),
            ]);
        }
        $info = [
            'vip'      => $vip,
            'vipName'  => $userGroup->level_name,
            'discount' => $userGroup->permission->discount,
        ];
        return new JsonResponse(['status' => 1, 'data' => $info, 'msg' => __('messages.successfully_obtained')]);
    }

    public function getLog()
    {

        //$k = $this->request()->get('k', 0);
        $d = $this->request()->get('d', 'laravel-cli-' . date('Y-m-d'));
        $f = storage_path() . '/logs/' . $d . '.log';
        if (file_exists($f)) {
            $r = file_get_contents(storage_path() . '/logs/' . $d . '.log');
        } else {
            $r = 'nolog';
        }
        //$k ? Redis::set('log', $k) : Redis::del('log');

        //dd();
        return new Response($r);
    }

    public function aa()
    {
        throw new HttpResponseException(JsonResponse::create([
            'status' => 0,
            'msg'    => __('messages.Api.aa.login_permission_denied')
        ]));
        return true;
    }

    public function platformGetUser()
    {
        return JsonResponse::create([
            'data' => [
                'uuid'      => 1 ? '888' . mt_rand(10000, 90000) : 88876597,
                'nickename' => 1 ? 'test' . mt_rand(100, 900) : "test767",
                'token'     => time(),
            ]
        ]);
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
            'sign'     => 'sign',
            'callback' => 'callback',
            'httphost' => '地址',
        ];
        $validator = Validator::make($request->all(), [
            'sskey'    => 'required',
            'sign'     => 'required',
            'httphost' => 'required',
            'callback' => 'required|max:15|min:5',
        ], [
            'sskey'    => ':attribute' . __('messages.Api.platform.not_empty'),
            'sign'     => ':attribute' . __('messages.Api.platform.not_empty'),
            'callback' => ':attribute' . __('messages.Api.platform.wrong_length'),
            'httphost' => ':attribute' . __('messages.Api.platform.not_empty'),
        ], $attributes);
        if ($validator->fails()) {
//            return new Response($validator->errors()->all());         //显示所有错误组成的数组
            return new Response(__('messages.Api.platform.wrong_param', ['num' => 1002]));
        }

        $sskey = $request->get("sskey");
        $callback = $request->get("callback");
        $sign = $request->get("sign");
        $httphost = $request->get("httphost", 0);
        $origin = $request->get("origin", 0);
        $m = $request->get("m");

        if (!$this->make("redis")->exists("hplatforms:$origin")) {
            return new Response(__('messages.Api.platform.wrong_param', ['num' => 1001]));

        }

        $platforms = $this->make("redis")->hgetall("hplatforms:$origin");
        $open = isset($platforms['open']) ? $platforms['open'] : 1;
        $plat_code = $platforms['code'];
        if (!$open) {
            return new Response(__('messages.Api.platform.closed'));

        }
        //if (empty($sskey) || empty($callback) || empty($sign) || empty($httphost)) return new Response("1002 接入方提供参数不对");

        $key = $platforms['key'];
        Log::channel('plat')->info("$plat_code 项目 dealSign:sskey=$sskey, callback=$callback, sign=$sign, httphost=$httphost");
        $sign_data = [$sskey, $callback, $key];
        if (!$this->checkSign($sign_data, $sign) && config('app.debug') == false) {
            return new Response(__('messages.Api.platform.wrong_sign'));
        }
//        if ($estimatedSign != $sign) return new Response("接入方校验失败");
//    $callback = 2650010;
        $room = $callback;
        if (!resolve(UserService::class)->getUserByUid($room)) {
            return new Response(__('messages.Api.platform.room_does_not_exist'));
        }

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
        if (empty($data)) {
//            return new Response("接入方数据获取失败" . $url . " $data" . "  返回：$res");
            return new Response(__('messages.Api.platform.data_acquisition_failed',
                [
                    'url'  => $url,
                    'data' => $data,
                    'res'  => $res
                ]
            ));
        }
        if (empty($data['uuid'])) {
            return new Response(__('messages.Api.platform.uuid_does_not_exist'));
        }
        if (empty($data['nickename'])) {
            return new Response(__('messages.Api.platform.empty_nickname'));
        }


        //注册
        $prefix = $platforms['prefix'];
        $username = $prefix . '_' . $data['nickename'] . "@platform.com";
        $users = UserSer::getUserByUsername($username);//Users::where('origin', $origin)->where('uuid', $data['uuid'])->first();
        $password_key = "asdfwe";
        $password = $data['nickename'] . $password_key;
        if (empty($users)) {
            $user = [
                'username' => $username,
                'nickname' => $prefix . '_' . $data['nickename'],
                'sex'      => 0,
                'uuid'     => $data['uuid'],
                'password' => $password,
                'xtoken'   => $data['token'],
                'origin'   => $origin,
            ];

            $uid = resolve(UserService::class)->register($user);
            Log::channel('plat')->info("$plat_code 项目 注册:" . json_encode($user) . '-' . (string)$uid);
            if (!$uid) {
//                return new Response("用户不存在" . json_encode($user) . $uid . $res);
                return new Response(__("messages.Api.platform.user_does_not_exist", [
                    'user' => json_encode($user),
                    'uid'  => $uid,
                    'res'  => $res
                ]));
            }

            AgentsRelationship::create([
                'uid' => $uid,
                'aid' => $platforms['aid'],
            ]);

            $this->userInfo = resolve(UserService::class)->getUserByUid($uid);
            if (empty($this->userInfo)) {
                return new Response(__("messages.Api.platform.failed_to_get_userInfo", [
                    'user' => json_encode($user),
                    'uid'  => $uid,
                    'res'  => $res
                ]));
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

        //偵測客戶端裝置 (11:pc/41:安卓H5/51:iosH5)
        if ($m === '1') {
            $clientOrigin = Agent::isAndroidOS() ? 41 : 51;
        } else {
            $clientOrigin = 11;
        }

        Session::put('platformId', $origin);
        app('events')->dispatch(new \App\Events\Login($this->userInfo, true, $clientOrigin));

        resolve(UserService::class)->getUserReset($this->userInfo['uid']);

        // 此时调用的是单实例登录的session 验证
        if (Auth::guest() || (Auth::check() && Auth::id() != $this->userInfo['uid'])) {
            if (!Auth::attempt([
                'username' => $this->userInfo['username'],
                'password' => $password,
            ])) {
                return JsonResponse::create([
                    'status' => 0,
                    'data'   => $this->userInfo['uid'].' '.$this->userInfo['username'],
                    'msg'    => __('messages.Api.platform.wrong_username_or_pwd')
                ]);
            };
        }

        // locale
        $locale = $this->filterSupportedLocale($data['locale']);
        $userAttrService = resolve(UserAttrService::class);
        $userAttrService->set($this->userInfo['uid'], 'locale', $locale);

        Session::put('httphost', $httphost);

        // 判斷手機版或 PC 版
        if ($m === '1') {
            $url = "/m/room/$room?httphost=". urlencode($httphost);
        } elseif ($m === '-1') {
            // flash 版 (將於 2020 年底廢除)
            $h5 = SiteSer::config('h5') ? "/h5" : "";
            $url = "/$room$h5?httphost=$httphost";
        } else {
            // 新 PC 直播間
            $url = "/room/$room?httphost=". urlencode($httphost);
        }
        return RedirectResponse::create($url);
    }

    public function checkSign($sign_data, $expect_sign)
    {
        return md5(implode('', $sign_data)) == $expect_sign;
    }

    /**
     *
     */
    public function get_lcertificate()
    {
        //get certificate
        $certificate = resolve(SafeService::class)->getLcertificate("socket");
        if (!$certificate) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Api.get_lcertificate.out_of_ticket')]);
        }
        return ['status' => 1, 'data' => ['datalist' => $certificate], 'msg' => __('messages.success')];
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
        if (!$userInfo->isHost()) {
            return JsonResponse::create([
                'status' => 0,
                'data'   => ['num' => 0],
            ]);
        }
        return JsonResponse::create([
            'status' => 1,
            'data'   => ['num' => $this->getUserAttensCount(Auth::id())],
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
        if (strpos($pid, ',')) {

            $A_pid = explode(',', $pid);
            $userService = resolve(UserService::class);
            $A_data = array();
            foreach ($A_pid as $S_pid) {
                $O = (object)array();
                $O->uid = $S_pid;
                $O->status = $userService->checkFollow($uid, $S_pid) - 0;
                array_push($A_data, $O);
            }
            return new JsonResponse([
                'status' => 1,
                'data'   => $A_data,
                'msg'    => __('messages.Api.Follow.search_following')
            ]);
        } else {
            if (!$pid) {
                return JsonResponse::create([
                    'status' => 0,
                    'msg'    => __('messages.Mobile.statistic.param_error'),
                ]);
            };
            if (!in_array($ret, [0, 1, 2]) || !$pid) {
                return JsonResponse::create([
                    'status' => 0,
                    'msg'    => __('messages.Mobile.statistic.param_error'),
                ]);
            };
            //不能关注自己
            if (($ret != 0) && ($uid == $pid)) {
                return JsonResponse::create([
                    'status' => 0,
                    'msg'    => __('messages.Api.Follow.can_not_follow_yourself'),
                ]);
            }
            $userService = resolve(UserService::class);
            $userInfo = $userService->getUserByUid($pid);

            if (!$userInfo) {
                return JsonResponse::create([
                    'status' => 0,
                    'msg'    => __('messages.unknown_user'),
                ]);
            }

            //查询关注操作
            if ($ret == 0) {
                if ($userService->checkFollow($uid, $pid)) {
                    return new JsonResponse(['status' => 1, 'msg' => __('messages.Api.Follow.already_followed')]);
                } else {
                    return new JsonResponse(['status' => 0, 'msg' => __('messages.Api.Follow.not_followed')]);
                }
            }


            //添加关注操作
            if ($ret == 1) {
                $follows = intval($this->getUserAttensCount($uid));
                if ($follows >= 1000) {
                    return new JsonResponse(['status' => 3, 'msg' => __('messages.Api.Follow.limit')]);
                }

                if ($userService->setFollow($uid, $pid)) {
                    return new JsonResponse(['status' => 1, 'msg' =>  __('messages.Api.Follow.followed_success')]);
                } else {
                    return new JsonResponse(['status' => 0, 'msg' => __('messages.Api.Follow.can_not_repeat_follow')]);
                }
            }

            //取消关注操作
            if ($ret == 2) {
                if ($userService->delFollow($uid, $pid)) {
                    return new JsonResponse(['status' => 1, 'msg' => __('messages.Api.Follow.unfollowed_success')]);
                } else {
                    return new JsonResponse(['status' => 0, 'msg' => __('messages.Api.Follow.unfollowed_failed')]);
                }
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
        if (!$userInfo) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.unknown_user')]);
        }

        //发送内容检测
        $msg = $request->get('msg');
        $len = $this->count_chinese_utf8($msg);
        if ($len < 1 || $len > 200) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Api.letter.word_limit')]);
        }


        //判断级别发送资格
        if ($userInfo['roled'] == 0 && $userInfo['lv_rich'] < 3) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Api.letter.lv_rich_limit')]);
        }

        //判断私信发送数量限制
        $userService = resolve(UserService::class);

        if (!$userService->checkUserSmsLimit($sid, 1000, 'video_mail')) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Api.letter.mail_amount_limit')]);
        }

        //发送私信
        $send = Messages::create([
            'content'  => htmlentities($msg),
            'send_uid' => $sid,
            'rec_uid'  => $rid,
            'category' => 2,
            'status'   => 0,
            'created'  => date('Y-m-d H:i:s')
        ]);


        //更新发送次数统计
        if (!$send || !$userService->updateUserSmsTotal($sid, 1, 'video_mail')) {
            return new JsonResponse(['status' => 0, __('messages.sent_failed')]);
        }

        return new JsonResponse(['status' => 1, 'msg' =>  __('messages.sent_successful')]);
    }


    /**
     * [获取余额接口]
     * @return JsonResponse
     * @todo        待优化
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
        if (!$userInfo['uid']) {
            return new JsonResponse(['ret' => 2, 'msg' => 'Get user information was failled']);
        }

        if (!$userInfo['status']) {
            return new Response(0);
        }
        $status = $this->make('request')->get('status');

        return new JsonResponse(
            [
                'status' => 1,
                'data'   => [
                    'Pending'   => $this->getAvailableBalance($userInfo['uid'], 4)['availmoney'],
                    'moderated' => $this->getAvailableBalance($userInfo['uid'], 0)['availmoney']
                ],
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
        if (!$uid) {
            return new JsonResponse(['status' => 0]);
        }
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

        if (sizeof($lastChargeUsers) < 1) {
            return new JsonResponse (['status' => 0]);
        }

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
        if (!$filename) {
            return new Response('access is not allowed', 500);
        }
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
        $data = json_decode(file_get_contents(route('downrtmp')));
        $jsonFormat = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $method = 'AES-128-CBC';
        $iv = base64_decode($data->iv);
        $key = SiteSer::config('downrtmp_key');
        $dataDecrypt = openssl_decrypt($data->data, $method, $key, 0, $iv);
        $dataDecryptFormat = json_encode(json_decode($dataDecrypt), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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
            if (!$ktv['status']) {
                continue;
            }
            //跳过人数多的
            if ($minUserNo <= $ktv['total']) {
                continue;
            }
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
            if (empty($rtmp_down)) {
                continue;
            }
            $minUserNo = $ktv['total'];
            $sid = $redis->hget('hvedios_ktv_set:' . $rid, 'sid');
            $minHost['rid'] = $rid;
            $minHost['total'] = $ktv['total'];

            $rtmp_down = explode('@@', $rtmp_down[0]);
            switch ($rtmp_down[1]) {
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

        Log::info("棋牌接口：" . json_encode($minHost));
        //
        $method = 'AES-128-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $downRTMP['data'] = openssl_encrypt(json_encode($minHost, JSON_UNESCAPED_SLASHES), $method,
            (string)SiteSer::config('downrtmp_key'), 0, $iv);
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
        $time = dechex(time() + $down_expire_sec);
        $tmp_uri = parse_url($uri, PHP_URL_PATH);
        $uri = trim($tmp_uri);
        $k = hash('md5', $rtmp_cdn_key . $uri . $time);
        return [
            'sign' => $k,
            't'    => $time
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
            'k'    => $k,
            'time' => $time
        ];
    }

    /* 主播上傳海報 */
    public function coverUpload(Request $request)
    {
        $uid = Auth::id();
        $imageContent = file_get_contents('php://input');
        if (empty($imageContent)) {
            return JsonResponse::create(['msg' => __('messages.Api.coverUpload.empty_img')]);
        } else {
            $fileName = $uid . '_' . time() . '.jpg';
            if (Storage::put('uploads/s88888/anchor/' . $fileName, $imageContent)) {
                resolve(UserService::class)->updateUserInfo($uid, ['cover' => $fileName]);

                /* 因應zimg上傳方法需做resource處理 */
                $fo = fopen(Storage::path('uploads/s88888/anchor/' . $fileName), 'r');

                /* 主播海報檔案處理並更新主播資料 */
                $imgData = resolve(SystemService::class)->upload(Auth::user()->toArray(), $fo);
                info('主播海報zimg資訊: ' . var_export($imgData, true));
                fclose($fo);

                if (empty($imgData['ret'])) {
                    JsonResponse::create(['msg' => __('messages.Api.coverUpload.zimg_upload_failed')]);
                }

                /* 更新主播海報資訊 */
                resolve(GuardianService::class)->coverTrans($imgData['info']['md5']);

                return JsonResponse::create(['msg' => __('messages.Api.coverUpload.upload_success')]);
            } else {
                return JsonResponse::create(['msg' => __('messages.Api.coverUpload.upload_failed')]);
            }
        }
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

        // $arr = include Storage::path('cache/anchor-search-data.php');;

        $userServer = resolve(UserService::class);
        $arr = $userServer->anchorlist();

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
        $isPC = strpos(request()->server('HTTP_REFERER'), '/h5');//HTTP_REFERER 如有/h5,則為二站pc
        $locale = strtolower(App::getLocale());//判斷user對應的禮物名稱語系 zh, zh_TW, zh_HK, en
        $sortJumpEgg = false;//跳蛋禮物是否放在最後排序

        if (SiteSer::siteId() === 2 && $isPC) {
            $sortJumpEgg = true;
        }

        $getGoodsList = $this->apiService->getGoodsList($sortJumpEgg, $locale);

        return JsonResponse::create(['data' => ['list' => $getGoodsList]]);
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
        } else {
            if ($key_words === null) {// key不存在时
                $result['status'] = 0;
            } else {
                $result['status'] = 1;
            }
        }
        $result['msg'] = __('messages.success');
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
            return new JsonResponse(['data' => '', 'status' => 0, __('messages.Api.rankListGift.empty_uid')]);
        }
        $score = $this->make('redis')->zscore('zvideo_live_times', $uid);
//lvideo_live_list:2653776:103
        $lrange_key = 'lvideo_live_list:' . $uid . ':' . $score;
        $lrange = $this->make('redis')->lrange($lrange_key, 0, 50);
        if (empty($lrange)) {
            return new JsonResponse(['data' => '', 'status' => 0]);
        }
        $data = $this->_formatLiveList($lrange);
        return new JsonResponse(['data' => $data, 'msg' => __('messages.success')]);
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
            return new JsonResponse(['data' => '', 'status' => 0, 'msg' =>  __('messages.Api.rankListGift.empty_uid')]);
        }

        /**
         * 从redis中获取主播的周排行榜
         * 返回格式： ['uid'=>'score']
         */
        $zrange_key = 'zrange_gift_week:' . $uid;
        $score = $this->make('redis')->ZREVRANGEBYSCORE($zrange_key, '+inf', '-inf',
            ['limit' => ['offset' => 0, 'count' => 30], 'withscores' => true]);
        if (empty($score)) {
            return new JsonResponse(['data' => '', 'status' => 0, 'msg' => __('messages.Api.rankListGift.empty_data')]);
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
        return new JsonResponse(['data' => ['list' => $data], 'msg' => __('messages.success')]);

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
            $live['uid'] = $arr[0];
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
            'msg'    => __('messages.failed_to_obtained'),
        ];

        if (Auth::check()) {
            $res['status'] = 1;
            $res['data']['sid'] = resolve(RedisCacheService::class)->sid(Auth::id());
            $res['msg'] = __('messages.success');
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
            'data'   => [
                'headimg' => '',
            ],
        ];
        if (!$uid) {
            return new JsonResponse($data);
        }
        $headimg = resolve(UserService::class)->getUserInfo($uid, 'headimg');
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
