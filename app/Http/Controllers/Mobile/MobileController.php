<?php
/**
 * 手機資訊 控制器
 * @date 2020-02-26
 * @apiDefine Mobile 手機資訊
 */

namespace App\Http\Controllers\Mobile;

use App\Events\Login;
use App\Facades\Mobile;
use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Http\Controllers\Controller;
use App\Models\AppCrash;
use App\Models\DomainList;
use App\Models\Goods;
use App\Models\ImagesText;
use App\Models\MobileUseLogs;
use App\Models\Pack;
use App\Models\Users;
use App\Models\Messages;
use App\Models\AppMarket;
use App\Models\UserModNickName;
use App\Services\AnnouncementService;
use App\Services\I18n\PhoneNumber;
use App\Services\LoginService;
use App\Services\Message\MessageService;
use App\Services\RedisCacheService;
use App\Services\Site\SiteService;
use App\Services\Sms\SmsService;
use App\Services\User\UserService;
use App\Services\UserAttrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Mews\Captcha\Facades\Captcha;
use App\Services\Auth\JWTGuard;
use Illuminate\Support\Facades\Input;

class MobileController extends Controller
{
    const ACTIVITY_LIST_PAGE_SIZE = 15;
    const MOUNT_LIST_PAGE_SIZE = 0;

    /* 本機快取存活時間 */
    const APCU_TTL = 1;

    protected $announcementService;
    protected $redisCacheService;

    public function __construct(AnnouncementService $announcementService, RedisCacheService $redisCacheService)
    {
        $this->announcementService = $announcementService;
        $this->redisCacheService = $redisCacheService;
    }

    /**
     * 移动端首页
     * 美女主播x4   全部主播x4
     */
    public function index()
    {
        $lists = [
            'rec' => [
                'key' => 'rec',
                'num' => 4,
            ],
            'all' => [
                'key' => 'all',
                'num' => 4,
            ],
        ];
        $redis = $this->make('redis');
        foreach ($lists as $key => &$list) {
            $list['data'] = json_decode($redis->get('m:index:list:' . $key));
            if (!$list['data']) {
                $tmpList = json_decode(@file_get_contents(base_path() . "/storage/app/public/s1/videolist$key.json"));
                $rooms = empty($tmpList->rooms) ? [] : $tmpList->rooms;
                $list['data'] = array_slice($rooms, 0, $list['num']);
                $redis->set('m:index:list:' . $key, json_encode($list['data']), 180);
            }
        }
        return JsonResponse::create(['data' => $lists, 'msg' => __('messages.success')]);
    }

    public function domains(Request $request)
    {
        try {
            $site = $request->get('site', 1);
            $result = DomainList::query()->get();
            $return = [
                'status' => 1,
                'data' => [
                    'greenips' => [],
                    'ips' => [],
                ]
            ];
            foreach ($result as $row) {
                if ($row->green)
                    $return['data']['greenips'][] = $row->url;
                else
                    $return['data']['ips'][] = $row->url;
            }
            $return = json_encode($return);
            Redis::set('domain:list', $return);
        } catch (\Exception $e) {
            $return = json_encode([
                'status' => 0,
                'msg' => $e->getTraceAsString(),
                'data' => [
                    'greenips' => [],
                    'ips' => [],
                ]
            ]);
        }
        return $return;
    }

    /**
     * 移动端排行榜
     * @author Young <Young@wisdominfo.my>
     * @return rank page
     */
    public function rank()
    {
        return $this->render('Mobile/rank', []);
    }

    public function test()
    {
        if (empty($_GET['s']) || $_GET['s'] != "axwv4w8khj23") {
            return;
        }
        $username = $_GET['uname'];
        $password = $_GET['pwd'];

        $jwt = $this->make('JWTAuth');


        $token = $jwt->login([
            'username' => $username,
            'password' => $password,
        ]);
        echo $token;
    }

    /**
     * @api {get} /user/info 获取用户信息
     * @apiGroup Mobile
     * @apiName 手機資訊
     * @apiVersion 1.0.0
     *
     * @apiSuccess {Int} uid 用户id
     * @apiSuccess {String} username 帳號
     * @apiSuccess {String} nickname 暱稱
     * @apiSuccess {String} headimg 頭像
     * @apiSuccess {Int} points 點卷
     * @apiSuccess {Int} roled 角色
     * @apiSuccess {Int} rid 房間id
     * @apiSuccess {Int} vip 貴族等級
     * @apiSuccess {Int} vip_end 貴族到期日
     * @apiSuccess {Int} lv_rich 財富等級
     * @apiSuccess {Int} lv_exp 主播經驗值
     * @apiSuccess {String} safemail 信箱
     * @apiSuccess {Int} icon_id 圖標id
     * @apiSuccess {String} gender 性別
     * @apiSuccess {Int} follows 關注數
     * @apiSuccess {Int} fansCount 跟隨人數
     * @apiSuccess {Int} system_tip_count 訊息發送數量
     * @apiSuccess {Int} transfer 轉帳權限(0:否/1:是)
     * @apiSuccess {String} birthday 生日
     * @apiSuccess {Int} province 省份
     * @apiSuccess {Int} city 城市
     * @apiSuccess {Int} nickcount 暱稱修改次數
     * @apiSuccess {Int} age 年齡
     * @apiSuccess {Int} guard_id 守護id
     * @apiSuccess {String} guard_name 守護名稱
     * @apiSuccess {String} guard_end 守護到期日
     * @apiSuccess {Int} guard_vaild_day 守護剩餘天數
     * @apiSuccess {Int} guard_shot_border  頭像邊框(0:關/1:開)
     *
     * @apiSuccessExample {json} 成功回應
     * {
    "status": 1,
    "data": {
    "uid": 9493540,
    "username": "rand9551107869@x.com",
    "nickname": "weine01",
    "headimg": "",
    "points": "0",
    "roled": "0",
    "rid": "",
    "vip": "0",
    "vip_end": "",
    "lv_rich": "1",
    "lv_exp": "1",
    "safemail": "",
    "icon_id": 0,
    "gender": "",
    "follows": 0,
    "fansCount": 0,
    "system_tip_count": 0,
    "transfer": "0",
    "birthday": "",
    "province": "0",
    "city": "0",
    "nickcount": 1,
    "age": 2020,
    "guard_id": "1",
    "guard_name": "黄色守护",
    "guard_end": "2020-03-26",
    "guard_vaild_day": 29,
    "guard_shot_border": 1
    },
    "msg": ""
    }
     *
     */
    public function userInfo()
    {
        $uid = Auth::id();
        $remote_js_url = SiteSer::config('remote_js_url');
        $userinfo = UserSer::getUserByUid($uid);

        if (!$userinfo) {
            return JsonResponse::create([
                'status' => 0,
                'msg' => __('messages.Mobile.userInfo.invalid_user'),
                'data' => $remote_js_url,
            ]);
        }

        $userfollow = $this->userFollowings();
        $hashtable = 'zuser_byattens:' . $uid;
        $by_atttennums = $this->make('redis')->zCount($hashtable, '-inf', '+inf');

        // 普通用户修改的权限 只允许一次
        $nickcount = 1;
        $uMod = UserModNickName::where('uid', $uid)->first();
        if ($uMod && $uMod->exists) {
            $nickcount = 0;
        }

        if(!empty($userinfo->guard_id)) {
            $guardianInfo = Users::find($uid)->guardianInfo;
            $guardVaildDay = ceil((strtotime($userinfo->guard_end) - time()) / 86400);
        }

        if ($userinfo->guard_id != 0 && time() > strtotime($userinfo->guard_end)) {
            $userinfo->guard_id = "0";
        }

        /* 取得新消息數量 */
        $mails = resolve(MessageService::class)->getMessageNotReadCount($uid, $userinfo->lv_rich);

        return JsonResponse::create([
            'status' => 1,
            'data'   => [
                'uid'               => $userinfo->uid,
                'username'          => $userinfo->username,
                'nickname'          => $userinfo->nickname,
                'headimg'           => $this->getHeadimg($userinfo->headimg),
                'points'            => $userinfo->points,
                'roled'             => $userinfo->roled,
                'rid'               => $userinfo->rid,
                'vip'               => $userinfo->vip,
                'vip_end'           => $userinfo->vip_end,
                'lv_rich'           => $userinfo->lv_rich,
                'lv_exp'            => $userinfo->lv_exp,
                'safemail'          => $userinfo->safemail ?? '',
//                'mails' => $this->make('messageServer')->getMessageNotReadCount($userinfo->uid, $userinfo->lv_rich),// 通过服务取到数量
                'icon_id'           => intval($userinfo->icon_id),
                'gender'            => $userinfo->sex,
                'follows'           => $userfollow,
                'fansCount'         => $by_atttennums,
//                'system_tip_count'  => Messages::where('rec_uid', $uid)->where('send_uid', 0)->where('status',
//                    0)->count(),
                'system_tip_count'  => $mails,
                'transfer'          => $userinfo->transfer,
                'birthday'          => $userinfo->birthday,
                'province'          => $userinfo->province,
                'city'              => $userinfo->city,
                'nickcount'         => $nickcount,
                'age'               => date('Y') - explode('-', $userinfo->birthday)[0],
                'guard_id'          => $userinfo->guard_id,
                'guard_name'        => __('messages.Guardian.name.' . $userinfo->guard_id) ?? '',
                'guard_end'         => $userinfo->guard_end ?? '',
                'guard_vaild_day'   => $guardVaildDay ?? 0,
                'guard_shot_border' => $guardianInfo->shot_border
            ],
        ]);
    }

    /**
     *用户特权
     */
    public function userPrivilege()
    {
        $uid = Auth::id();
        $user = Auth::user();

        //判断隐身权限
        $allowStealth = resolve(UserService::class)->getUserHiddenPermission($user);
        $return = [
            'allow_stealth' => $allowStealth,//可以隐身
            'hidden' => $allowStealth && $user->hidden,//当前隐身状态

        ];
        $return['vip'] = $user->vip;
        $return['vip_end'] = $user->vip_end;

        // 是贵族才验证 下掉贵族状态
        if ($user->vip && ($user->vip_end < date('Y-m-d H:i:s'))) {
            $return['vip'] = 0;
            $return['vip_end'] = null;
            $data = [
                'vip' => 0,
                'vip_end' => null,
            ];
            resolve(UserService::class)->updateUserInfo($uid, $data);

            // 删除坐骑
            Pack::where('uid', $uid)->where('gid', '<=', 120107)
                ->where('gid', '>=', 120101)->delete();
            $this->make('redis')->del('user_car:' . $uid);
        }

        return JsonResponse::create($return);
    }

    /**
     * 座驾列表
     */
    public function mountList()
    {
        $uid = Auth::id();
//        $page = $this->make("request")->input('page',1);
        $list = Pack::with('mountGroup')->where('uid', $uid)->simplePaginate(self::MOUNT_LIST_PAGE_SIZE);
//        $result['user'] = $this->userInfo;
        $result['list'] = $list->toArray();
        $result['equip'] = Redis::hgetall('user_car:' . $uid);
        //判断是否过期
        if ($result['equip'] != null && current($result['equip']) < time()) {
            Redis::del('user_car:' . $uid);//检查过期直接删除对应的缓存key
        }
        return JsonResponse::create($result);
    }

    /**
     * 装备坐骑
     */
    public function mount($gid)
    {
        $handle = $this->_getEquipHandle($gid);
        if (is_array($handle)) {
            return JsonResponse::create($handle);

        } else {
            return JsonResponse::create(['status' => 101, 'msg' => __('messages.unknown_error')]);
        }
    }

    /**
     * 装备操作逻辑处理
     * @param $gid
     * @return array|bool
     * @author D.C
     * @update 2014.12.10
     * 复制自MemberController
     */
    private function _getEquipHandle($gid)
    {

        $uid = Auth::id();
        if (!$gid || !$uid) {
            return false;
        }

        $pack = Pack::where('uid', $uid)->where('gid', $gid)->first();
        if (!$pack) {
            return false;
        }

        /**
         * 判定道具类型,
         * @todo 跟[Antony]确认，规定category字段1000-1999ID范围为可装备道具,增加查询道具类型。
         */
        $goods = Goods::find($gid);
        if ($goods['gid'] < 120001 || $goods['gid'] > 121000) {
            return ['status' => 2, 'msg' => __('messages.Mobile._getEquipHandle.use_in_room')];
        }

        /**
         * 使用Redis进行装备道具
         * @todo   目前道具道备只在Redis上实现，并未进行永久化存储。目前产品部【Antony】表示保持现状。
         * @update 2014.12.15 14:35 pm (Antony要求将道具改为同时只能装备一个道具！)
         */
        $redis = $this->make('redis');
        $redis->del('user_car:' . $uid);
        $redis->hset('user_car:' . $uid, $gid, $pack['expires']);
        return ['status' => 1, 'msg' => __('messages.success')];
    }

    /**
     * 取消坐骑
     */
    public function unmount()
    {
        $this->make('redis')->del('user_car:' . Auth::id());//检查过期直接删除对应的缓存key
        return JsonResponse::create(['status' => 0, 'msg' => __('messages.success')]);
    }

    /**
     * 隐身
     */
    public function stealth($status)
    {
        $uid = Auth::id();
        if (!$uid) return JsonResponse::create(['status' => 0, 'message' => __('messages.unknown_user')]);
        $userServer = resolve(UserService::class);
        $user = $userServer->getUserByUid($uid);
        //判断用户是否有隐身权限
        if (!$userServer->getUserHiddenPermission($user)) return JsonResponse::create(['status' => 0, 'msg' => __('messages.permission_denied')]);

        //更新数据库隐身状态
        Users::where('uid', $uid)->update(['hidden' => $status]);
        //更新用户redis
        $userServer->getUserReset($uid);

        return JsonResponse::create(['status' => 1, 'msg' => __('messages.success')]);
    }

    /**
     * 验证码
     */
    public function captcha()
    {
        return Captcha::create()->header(session()->getName(), session()->getId(), true);
//        $png = $captcha->getContent();
//        return JsonResponse::create(['captcha' => base64_encode($png), Session::getName() => Session::getId()]);
    }


    /**
     * 移动端登录
     */
    public function login(Request $request)
    {
        $credentials = [];
        $useMobile = $request->post('use_mobile', 0) == '1';
        $site_id = SiteSer::siteId();
        $redis = resolve('redis');
        $cc_mobile = '';
        $uid = -1;

        if ($useMobile) {
            $cc = $request->post('cc', '');
            $mobile = $request->post('mobile', '');
            $code = $request->post('code', '');
            if (empty($cc) || empty($mobile) || empty($code)) {
                return $this->msg('Invalid request');
            }
            $mobile = PhoneNumber::formatMobile($cc, $mobile);

            $result = SmsService::verify(SmsService::ACT_LOGIN, $cc, $mobile, $code);
            if ($result !== true) {
                return $this->msg($result);
            }

            $cc_mobile = $cc.$mobile;
            $credentials['cc_mobile'] = $cc_mobile;
            $credentials['mobile_logined'] = true;
            $uid = UserSer::getUidByCCMobile($cc_mobile);
        } else {
            $username = $request->get('username');
            $password = $this->decode($request->get('password'));
            $captcha = $request->get('captcha');
            if (!app(SiteService::class)->config('skip_captcha_login')) {
                if (empty($captcha)) {
                    return JsonResponse::create(['status' => 0, 'msg' => __('messages.captcha_error')]);
                }

                /* 檢查驗證碼或自動化測試驗證(主播機器人項目) */
                if (!Captcha::check($captcha) && !app(LoginService::class)->autoCheck($captcha)) {
                    return JsonResponse::create(['status' => 0, 'msg' => __('messages.captcha_error')]);
                }
            }
            if (!$username || !$password) {
                return JsonResponse::create(['status' => 0, 'msg' => __('messages.Mobile.login.password_required')]);
            }

            $credentials['username'] = $username;
            $credentials['password'] = $password;
            $uid = UserSer::getUidByUsername($username);
            if (!$uid) {
                $uid = UserSer::getUidByNickname($username);
            }
        }

        $jwt = Auth::guard('mobile');

        $member = Users::find($uid);

        if ($member && $jwt->validate($credentials)) {
            $S_qq = Redis::hget('hsite_config:' . SiteSer::siteId(), 'qq_suspend');
            // freeze check
            if ($member->isFreeze()) {
                return JsonResponse::create(['status' => 0, 'msg' => __('messages.Mobile.login.account_block_30days_no_show', ['S_qq' => $S_qq])]);
            }
            // platform user check
            if ($member->wrongOrigin()) {
                return JsonResponse::create(['status' => 0, 'msg' => __('messages.must_login_on_platform')]);
            }
        }

        $user = null;
        if (!$jwt->attempt($credentials)) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Mobile.login.password_error')]);
        }

        $user = $jwt->user();
        $token = (string) $jwt->getToken();
        //添加是否写入sid判断
        $this->redisCacheService->setSidForMobile($uid, $token);
        $sidUser = $this->redisCacheService->sid($uid);
        if (empty($sidUser)) {
            $this->setStatus(0, __('messages.Mobile.login.token_error'));
            return $this->jsonOutput();
        }

        $statis_date = date('Y-m-d');
        MobileUseLogs::create([
            'imei' => $request->get('imei'),
            'uid' => $user->getAuthIdentifier(),
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'],
            'statis_date' => $statis_date,
        ]);
        $userfollow = $this->userFollowings();
        $hashtable = 'zuser_byattens:' . $user->uid;
        $by_atttennums = $this->make('redis')->zCount($hashtable, '-inf', '+inf');
        if ($user->pwd_change === null || $user->cpwd_time === null) {
            $user = (object)UserSer::getUserReset($user->uid);
        }

        /* ---用戶locale處理--- */
        $locale = $request->{locale} ?? '';
        $userAttrService = resolve(UserAttrService::class);
        if (!empty($locale)) {
            $userAttrService->set($user->uid, 'locale', $locale);
        }
        /* ---用戶locale處理 end--- */

        //更新最后的登录时间 & ip
//        app('events')->dispatch(new \Illuminate\Auth\Events\Login($user, true));
        app('events')->dispatch(new Login($user, true, $request->origin));

        return JsonResponse::create([
            'status' => 1,
            'data' =>
                [
                    'jwt' => (string)$jwt->getToken(),
                    'user' => [
                        'status' => 1,
                        'uid' => $user->uid,
                        'nickname' => $user->nickname,
                        'headimg' => $this->getHeadimg($user->headimg),
                        'points' => $user->points,
                        'roled' => $user->roled,
                        'rid' => $user->rid,
                        'vip' => $user->vip,
                        'vip_end' => $user->vip_end,
                        'lv_rich' => $user->lv_rich,
                        'lv_exp' => $user->lv_exp,
                        'safemail' => $user->safemail ?? '',
                        'icon_id' => (int) $user->icon_id,
                        'gender' => $user->sex,
                        'follows' => $userfollow,
                        'fansCount' => $by_atttennums,
                        'pwd_change' => SiteSer::config('pwd_change') ? $user->pwd_change : 1,
                        'cpwd_time' => $user->cpwd_time,
                    ],
                ]]);
    }

    public function domain()
    {
        $redis = resolve('redis');
        $redisDomains = $redis->get('domain:list:' . SiteSer::siteId());
        if (false == $redisDomains) {
            $backData = [
                'greenips' => [],
                'ips' => [],
            ];
            DomainList::orderBy('id', 'desc')->get()->each(function ($vo) use (&$backData) {
                if ($vo->green) {
                    $backData['greenips'][] = $vo->url;
                } else {
                    $backData['ips'][] = $vo->url;
                }
            });
            $redis->set('domain:list:' . SiteSer::siteId(), json_encode($backData));
        } else {
            $backData = json_decode($redisDomains, true);
        }
        return JsonResponse::create([
            'status' => empty($backData['ips']) ? 0 : 1,
            'data' => $backData
        ]);
    }

    public function changePwd()
    {
        $request = $this->request();
        $captcha = $request->input('captcha');

        if (!app(SiteService::class)->config('skip_captcha_login')) {
            if (empty($captcha)) {
                return JsonResponse::create(['status' => 0, 'msg' => __('messages.captcha_error')]);
            }
            if (!Captcha::check($captcha)) {
                return JsonResponse::create(['status' => 0, 'msg' => __('messages.captcha_error')]);
            }
        }
        return $this->doChangePwd($request);
    }

    /**
     * 用户关注人数
     */
    public function userFollowings()
    {

        // $arr = include(storage_path() . '/app/cache/anchor-search-data.php');
        $userServer = resolve(UserService::class);
        $arr = $userServer->anchorlist();
        $hasharr = [];
        foreach ($arr as $value) {
            $hasharr[$value['uid']] = $value;
        }
        unset($arr);
        $myfavArr = $this->make('redis')->zrevrange('zuser_attens:' . Auth::id(), 0, -1);
        $myfav = [];
        if (!!$myfavArr) {
            //过滤出主播
            foreach ($myfavArr as $item) {
                if (isset($hasharr[$item])) {
                    $myfav[] = $hasharr[$item];
                }
            }
        }
        return count($myfav);
    }

    public function logintest()
    {
        return JsonResponse::create(['status' => Auth::check(), 'user' => Auth::user()]);
    }

    /**
     *移动端轮播图获取
     */
    public function sliderList()
    {
        $list = Redis::get('vbos.images:type:' . $this->request()->input('type', 1));
        $list = collect(json_decode($list))->map(function ($img) {
            return [
                'id' => $img->id,
                'url' => $img->url,
                'img_name' => $img->temp_name,
            ];
        });
        return new JsonResponse(['data' => $list]);
    }

    /**
     * 移动端活动列表
     */
    public function activityList()
    {
        $page = $this->request()->input('page', 1);
        $redis = $this->make('redis');
        /*  if ($list = $redis->get('image.text:activity.list:page:' . $page)) {
              return JsonResponse::create()->setContent($list);
          }*/
        $list = ImagesText::where('dml_flag', '<>', 3)->where('pid', 0)->selectRaw('img_text_id id,title,temp_name,url,init_time')
            ->orderBy('sort')->orderBy('img_text_id', 'desc')->simplePaginate(static::ACTIVITY_LIST_PAGE_SIZE);
        $redis->set('image.text:activity.list:page:' . $page, $list->toJson(), 180);
        return new JsonResponse(['data' => $list->toArray(), 'msg' => __('messages.success')]);
    }

    /**
     * 移动端活动详情
     */
    public function activityDetail($id)
    {
        $redis = $this->make('redis');
        /* if ($activity = $redis->get('image.text:activity.detail:id:' . $id)) {
             return JsonResponse::create()->setContent($activity);
         }*/
        $activity = ImagesText::where('dml_flag', '<>', 3)->where('pid', $id)->selectRaw($id . ' id,temp_name,init_time')->first();

        //如果为空，返回默认json数据
        $is_array = [
            'id' => $id,
            'init_time' => '',
            'title' => '',
            'url' => [],
        ];
        if (!$activity) return JsonResponse::create($is_array);
        $parent = ImagesText::where('dml_flag', '<>', 3)->select('title')->find($id);
        $activity->title = $parent->title;
        $activity->url = explode(',', $activity->temp_name);
        $activity->setHidden(['temp_name']);
        $redis->set('image.text:activity.detail:id:' . $id, $activity->toJson(), 180);

        //   return JsonResponse::create($activity);
        return new JsonResponse(['data' => $activity, 'msg' => __('messages.success')]);
    }

    /**
     * 主播列表
     * @param $type string all:所有|rec:推荐|ord:一对一|ticket:一对多
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function videoList($type)
    {
        header('Content-type: application:json;charset=utf-8');
        header('Location: ' . "/api/storage/s1/videolist$type.json");
//        $list = @file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/videolist$type.json") ?: '[]';
//        return JsonResponse::create()->setContent($list);
    }

    /**
     * 用户关注列表
     */
    public function userFollowing()
    {
//        $page = $this->request()->input('page', 1);
//        $userServer = resolve(UserService::class);
        // $arr = include Storage::path('cache/anchor-search-data.php');
        $userServer = resolve(UserService::class);
        $arr = $userServer->anchorlist();
        $hasharr = [];
        foreach ($arr as $value) {
            $hasharr[$value['uid']] = $value;
        }
        unset($arr);
        $myfavArr = $this->make('redis')->zrevrange('zuser_attens:' . Auth::id(), 0, -1);

        $myfav = [];
        if ($myfavArr) {
            //过滤出主播
            foreach ($myfavArr as $uid) {
                if (isset($hasharr[$uid])) {
                    $myfav[] = $hasharr[$uid];
                }
            }
        }
        return JsonResponse::create(['data' => ['data' => ($myfav)]]);
    }

    /**
     * 统计留存接口,一天只保存一条
     *   'imki'
     *   'uid'
     *   'ip'
     */
    public function statistic()
    {
        //如果有则更新，没有则创建
        $request = $this->request();
        $imei = $request->input('imei');
        $uid = $request->input('uid') ?: null;
        $ip = $request->input('ip') ?: '';
        if (!$imei) return JsonResponse::create([
            'status' => 0,
            'msg' => __('messages.Mobile.statistic.param_error'),
        ]);
        if ($uid && !Users::find($uid)) {
            return JsonResponse::create([
                'status' => 0,
                'msg' => __('messages.Mobile.statistic.param_error'),
            ]);
        }
        $statis_date = date('Y-m-d');
        MobileUseLogs::create([
            'imei' => $imei,
            'uid' => $uid,
            'ip' => $ip,
            'statis_date' => $statis_date,
        ]);
        return JsonResponse::create(['status' => 1, 'data' => __('messages.success')]);
    }


    /**
     * @api {post} /app/version app版本
     * @apiGroup Mobile
     * @apiName app版本
     * @apiVersion 1.0.0
     *
     * @apiParam {Int} ver_code 版本號碼(EX: 21700)
     * @apiParam {Int} [branch] 版本分支(目前前端寫死固定值)
     *
     * @apiParamExample {json} Request-Example:
     *{
    "branch":1,
    "ver_code":21200
    }
     *
     * @apiError (Error Status) 0
     *
     * @apiSuccess {Int} id 流水號
     * @apiSuccess {String} ver 版本号
     * @apiSuccess {Int} ver_code 内部版本号
     * @apiSuccess {Int} branch 版本类型，1=stable|2=Alpha|3=Beta|4=RC|5=Dev|6=主播版
     * @apiSuccess {String} content 版本更新内容
     * @apiSuccess {Int} mandatory 是否为强制更新，0=否|1=是
     * @apiSuccess {String} released_at 发布时间
     * @apiSuccess {String} apk_filename apk文件名
     * @apiSuccess {String} created_at 创建时间
     * @apiSuccess {String} md5
     * @apiSuccess {Int} site_id 站点ID
     * @apiSuccess {String} web_url 外部连结地址
     *
     * @apiSuccessExample {json} 成功回應
     *{
    "status": 1,
    "data": {
    "1": {
    "id": 17,
    "ver": "2.16.0",
    "ver_code": 21600,
    "branch": 1,
    "content": "55555",
    "mandatory": 1,
    "released_at": "2020-06-08 00:00:00",
    "apk_filename": "2.16.0-Stable-3.apk",
    "created_at": "2019-06-03 17:20:46",
    "md5": "f2c2af4ebbb4c82a34f65a6e36ba3640",
    "site_id": 1,
    "web_url": "http:\/\/dev.v1.com"
    }
    },
    "msg": ""
    }
     */
    public function appVersion(Request $request)
    {
        $branches = $request->get('branch');
        $verCode = (int) $request->get('ver_code');

        if ($branches) {
            $branches = explode(',', $branches);
        } else {
            $branches = range(1, 5);
        }

        $versions = [];
        $status = 0;

        foreach ($branches as $branch) {
            $isIOS = Mobile::checkIos();

            $version = $isIOS ?
                Mobile::getLastIosVersion($verCode, $branch) :
                Mobile::getLastAndroidVersion($verCode, $branch);

            if ($version) {
                $versions[$branch] = collect($version)->toArray();
                $status = 1;
            } else {
                $versions[$branch] = [];
            }
//            if ($version) $versions[$branch] = $version;
        }

        return JsonResponse::create(['status' => $status, 'data' => $versions]);
    }

    public function appVersionIOS(Request $request)
    {
        $branches = $request->get('branch');
        $verCode = (int) $request->get('ver_code');

        if ($branches) {
            $branches = explode(',', $branches);
        } else {
            $branches = range(1, 5);
        }

        $versions = [];

        foreach ($branches as $branch) {
            $version = Mobile::getLastIosVersion($verCode, $branch);
            if ($version) {
                $v = collect($version)->toArray();

                /* 檢查是否強更 */
                $v['mandatory'] = (int) Mobile::checkIOSForceUpdate($verCode, $branch);
                $versions[$branch] = $v;
            }
//            if ($version) $versions[$branch] = $version;
        }
        return JsonResponse::create(['status' => empty($versions[1]) ? 0 : 1, 'data' => $versions]);
    }

    public function searchAnchor()
    {
        //$uname = isset($_GET['nickname'])?$_GET['nickname']:'';//解码？
        $uname = $this->make('request')->get('nickname', '');
        // $arr = include storage_path('app') . '/cache/anchor-search-data.php';//BASEDIR . '/app/cache/cli-files/anchor-search-data.php';
        $userServer = resolve(UserService::class);
        $arr = $userServer->anchorlist();

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
        return new JsonResponse(['data' => ['rooms' => $data], 'status' => 1]);
    }

    /*
     * 获取粉丝详情 by desmond 2017-12-21
     */
    public function getFans()
    {

        $uid = Auth::id();
        $page = $this->request()->input('page') ?: '1';
        $page_size = $this->request()->input('pageCount') ?: '1';
        $page_num = $page * $page_size;
        if (!$uid) return JsonResponse::create(['status' => 0, 'msg' => __('messages.Mobile.getFans.host_id_not_exist')]);
        $keys = 'zuser_byattens:' . $uid;
        $redis = $this->make('redis');
        $zuser = $redis->zrange($keys, 0, -1);
        //总页数
        $count_page = ceil(count($zuser) / $page_size);
        $zuserinfo = [];
        foreach ($zuser as $key => $value) {
            if ($key < $page_num && $key >= $page_num - $page_size) {
                $zuserinfo[] = $value;
            }
        }
        $insertArr = [];
        foreach ($zuserinfo as $key => $value) {
            $user = UserSer::getUserByUid($value);
            $info = $user ? $user->only(['uid', 'nickname', 'rich', 'headimg', 'lv_exp', 'lv_rich', 'vip', 'roled']) : [];
            if (count($info)) {
                $info['headimg'] .= '.jpg';
            }

            array_push($insertArr, $info);
        }
        $result['data']['userinfo'] = $insertArr;
        $result['data']['page'] = $page;
        $result['data']['page_count'] = $count_page;

        return JsonResponse::create($result);

    }

    public function saveCrash()
    {
        /*
         *  一二站合并，原接口处理逻辑删掉，但保留该接口（防止移动端还有调用该接口导致报错）.
         */
        return JsonResponse::create(['status' => 1, 'msg' => __('messages.success')]);
    }

    /**
     * 密码修改
     */
    public function passwordChange()
    {
        $uid = Auth::id();
        $post = $this->request()->all();
        $post['original_password'] = $this->decode($post['original_password']);
        $post['new_password'] = $this->decode($post['new_password']);
        $post['re_new_password'] = $this->decode($post['re_new_password']);

        if (empty($post['original_password'])) {
            return JsonResponse::create(['status' => 0, 'data' => new \StdClass(), 'msg' => __('messages.Mobile.passwordChange.old_password_required')]);
        }

        if (strlen($post['new_password']) < 6 || strlen($post['re_new_password']) < 6) {
            return JsonResponse::create(['status' => 0, 'data' => new \StdClass(), 'msg' => __('messages.Mobile.passwordChange.more_or_equal_than_six_char_length')]);
        }

        if ($post['new_password'] != $post['re_new_password']) {
            return JsonResponse::create(['status' => 0, 'data' => new \StdClass(), 'msg' => __('messages.Mobile.passwordChange.new_password_is_not_the_same')]);
        }

        $old_password = resolve(UserService::class)->getUserInfo($uid, 'password');
        $new_password = md5($post['re_new_password']);
        if (md5($post['original_password']) != $old_password) {
            return JsonResponse::create(['status' => 0, 'data' => new \StdClass(), 'msg' => __('messages.Mobile.passwordChange.old_password_is_wrong')]);
        }
        if ($old_password == $new_password) {
            return JsonResponse::create(['status' => 0, 'data' => new \StdClass(), 'msg' => __('messages.Mobile.passwordChange.new_and_old_is_the_same')]);
        }

        $user = Users::find($uid);
        $user->password = $new_password;
        if (!$user->save()) {
            return JsonResponse::create(['status' => 0, 'data' => new \StdClass(), 'msg' => __('messages.Mobile.passwordChange.modify_failed')]);
        }
        resolve(UserService::class)->getUserReset($uid);
//        Auth::logout();
        return new JsonResponse(['status' => 1, 'data' => new \StdClass(), 'msg' => __('messages.success')]);
    }

    /**
     * 系统消息列表页面
     * @return JsonResponse
     */
    public function msglist()
    {
        $page_size = (int)$this->request()->get('page_size');
        $page_size = $page_size ? $page_size : 15;
//        $uid = Auth::id();

//        $list = Messages::where('rec_uid', $uid)
//            ->where('send_uid', 0)
//            ->orderBy('created', 'desc')
//            ->paginate($page_size);
//
//        //更新消息为已读状态
//        if (!$list->isEmpty()) {
//            Messages::where('rec_uid', $uid)
//                ->where('send_uid', 0)
//                ->where('status', 0)
//                ->update(['status' => 1]);
//        }

        // 调用消息服务
        $msg = resolve(MessageService::class);

        // 根据用户登录的uid或者用户消息的分页数据
        $list = $msg->getMessageByUidAndType(Auth::id(), 1, $page_size, Auth::user()->lv_rich);

        // 更新读取的状态
        $msg->updateMessageStatus(Auth::id());

        return JsonResponse::create([
            'status' => 1,
            'data'   => $list,
            'msg'    => __('messages.success'),
        ]);
    }

    /**
     * 應用配置
     * @return JsonResponse
     */
    public function appMarket()
    {
        $cdn = SiteSer::config('cdn_host')."/storage/uploads/s".SiteSer::siteId()."/oort/"; // 'http://s.tnmhl.com/public/oort';

        $uid = Auth::id();
        $list = AppMarket::where('status', 1)->where('site_id',SiteSer::siteId())->orderBy('order', 'asc')->get();

        $market = (object)array();
        $market->banner= array();
        $market->recommend= array();
        $market->allApp= array();
        $market->list= array();

        foreach($list as $S_list){

            $one = (object)array();
            $one->app_id = (string)$S_list['id'];
            $one->app_name = $S_list['name'];
            $one->app_desc = $S_list['desc'];
            $one->android_url = $S_list['android_url'];
            $one->ios_url = $S_list['ios_url'];
            $one->position = (string)$S_list['position'];

            if(!empty($S_list['image'])){
                //图片实际连结存在时 不导入cdn路径
                $one->pic = strpos($S_list['image'],'://')?$S_list['image']:$cdn.$S_list['image'];
            }else{
                $one->pic = '';
            }

            $position = $S_list['position'];

            if($position==1){
                array_push($market->banner,$one);
            }
            if($position==2){
                array_push($market->recommend,$one);
            }
            if($position==3){
                array_push($market->list,$one);
            }
            if($position==4){
                array_push($market->allApp,$one);
            }
        }

        return JsonResponse::create([
            'status' => 1,
            'data' => $market,
            'msg' => __('messages.success')
        ]);
    }

    /**
     * 官方聯繫方式
     * @return JsonResponse
     */
    public function official()
    {
        $official = Redis::hget('hsite_config:' . SiteSer::siteId(),'mobile_official');
        return JsonResponse::create([
            'status' => 1,
            'data' => $official,
            'msg' => __('messages.success')
        ]);
    }

    /**
     * 官方聯繫方式
     * @return JsonResponse
     */
    public function marquee()
    {
        $device = Input::get('device',2);

        /* 快取機制 */
        $list = Cache::remember('hmarquee:' . SiteSer::siteId() . ':list:' . $device, self::APCU_TTL, function() use($device) {

            /* 取得原redis資料 */
            $redisData = collect(json_decode(Redis::hget('hmarquee:' . SiteSer::siteId(), 'list')));

            /* 格式化資料 */
            $data = $redisData->filter(function ($val, $key) use($device) {
                return $val->device == $device && $val->status > 0;
            })->map(function ($val, $key) {
                $val = collect($val);
                $val->put('sorted', $val->pull('order'));
                $val->put('creat_time', $val->pull('id'));
                return $val;
            })->values();

            return $data;
        });

        return JsonResponse::create([
            'status' => 1,
            'data' => $list,
            'msg' => __('messages.success')
        ]);
    }

    /**
     * 登入公告
     * @param int device 裝置類型
     * @return JsonResponse
     */
    public function loginmsg(){
        try {
            $device = Input::get('device',1);

            $result = $this->announcementService->getLoginMsgByDevice($device);

            if(empty($result)) {
                $this->setStatus(0, __('messages.Mobile.loginmsg.no_data'));
            } else {
                $this->setStatus(1, __('messages.success'));
                $this->setRootData('data', $result);
            }

            return $this->jsonOutput();
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            $this->setStatus(999, __('messages.Mobile.loginmsg.no_data'));
            return $this->jsonOutput();
        }
    }
}
