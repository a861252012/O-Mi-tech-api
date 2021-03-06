<?php

namespace App\Http\Controllers;


use App\Events\Login;
use App\Facades\UserSer;
use App\Models\UserLoginLog;
use App\Models\Users;
use App\Services\I18n\PhoneNumber;
use App\Services\RedisCacheService;
use App\Services\Site\SiteService;
use App\Services\Sms\SmsService;
use App\Services\UserAttrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Mews\Captcha\Facades\Captcha;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Facades\SiteSer;


class LoginController extends Controller
{

    /**
     * TODO @1登录
     * a域名执行登录操作 牵涉到跨域跳转的问题
     * <p>
     * 此方法只用于在主域名中登录的具体方法，用户域名是限制走此方法的
     * </p>
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function login(Request $request)
    {
        $useMobile = $request->post('use_mobile', 0) == '1';
        if ($useMobile) {
            return $this->solveMobileLogin($request);
        }

        //获取值
//        $username = $request->get('username') ?: '';
//        $password = $request->get('password') ?: '';

        $username = $request->username ?? '';
        $password = $request->password ?? '';
        $locale = $request->{locale} ?? '';

        if (!isset($_REQUEST['_m'])) {
            $password = $this->decode($password); // 密码传递解密
        }
        $retval = $this->solveUserLogin($username, $password, $locale, app(SiteService::class)->config('skip_captcha_login'));


        return JsonResponse::create($retval);
    }

//    public function reloadLogin()
//    {
//        /**
//         * 如果登录 成功  就跳转到首页
//         */
//        $result = $this->doSynLogin();
//        if ($result) {
//            return new RedirectResponse('/');
//        }
//        /**
//         * 失败，就跳转到登录页面
//         */
//        $domain = SiteSer::config('login_domain');
//        $login_domain = array_rand(SiteSer::config('login_domain'), 1);
//        return new RedirectResponse('http://' . $domain[$login_domain] . '/passport');
//
//    }

    /**
     * 同步跳转登录 b 用户所属域名的方法
     *
     * <p>
     * 用户在 a 域名登录后（也就是login方法）,会返回通知地址，就是此方法，用于在b 域名上登录成功
     * 两次登录是通过双向加密验证获取数据的；
     * 加密秘钥在config.php中配置syn_login_encode_key
     * </p>
     */
    public function synLogin()
    {
        $result = $this->doSynLogin();
        if ($result) {
            $res = [
                'status' => 1,
                'msg' => $this->_sess_id,
                'uid' => Auth::id(),
            ];
            return new JsonResponse($res);
        }
        return new Response('Bad Request!', 404);
    }

    protected function doSynLogin()
    {
        /**
         * 通知时的数据加密后的字符串，通过解密进行试验
         */
        $code = $this->make('request')->get('code');
        parse_str($this->authcode($code, 'DECODE', SiteSer::config('syn_login_encode_key')), $get);

        if (empty($get)) {
            return false;
        }
        /**
         * 这是验证数据的时效性，code解析后其中会有time的时间戳，与现在时间进行对比；
         * 默认是30秒过期，
         */
        if (time() - $get['time'] > 30) {
            return false;
        }

        $uid = intval($get['uid']);
        if (!($member = Users::find($uid))) {
            return false;
        }
        $this->login_user = $member;
        resolve(RedisCacheService::class)->setSidForPC($uid, request()->session()->getId());

        /**
         * 如果传过来是记录几天免登陆的，操作cookie
         */
        $domainA = $this->make('request')->get('domainA');
        if ($get['v_remember']) {
            /**
             * 用于解决下面的cookie写入跨域问题
             */
            header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');

            //   $expireDay = 604800;//7*24*60*60
            //记住我的功能，将uid,|,用户名，密钥 一起md5加密，验证的时候可以用|分割，增加A域名地址信息irwin
            setcookie(
                self::CLIENT_ENCRY_FIELD, Auth::id() . '|' . $this->remypwdMd5($this->login_user['username']) . '|' . $domainA,
                time() + 604800, '/'
            );
            $day = date('Ymd');
            $times = intval($this->make('redis')->hget('hlogin_remember', $day));
            $this->make('redis')->hset('hlogin_remember', $day, ++$times);
        }
        return true;
    }

    public function isLogin()
    {
        return new Response(json_encode([
            'ret' => Auth::check(),
        ]));
    }

    /**
     * [solveUserLogin 用户登录验证]
     * <p>
     * 现在所有登录都是通过主域名的passport方法进行登录的；
     * 登录完成后跳转到用户对于的域名上去，所以牵涉到跨域；
     * example：主域名：www.a.com 用户所属域名：www.b.com
     *          先在 a 域名登录后保持session id到redis中的：huser_sid_a uid session_id
     *          成功后跳转到b域名上，通过解密验证后写入session，此时session id 在：huser_sid uid session_id中
     *          主域名和用户域名分开在不同的redis的key中
     * </p>
     *
     * @param  string  $username     [用户名称]
     * @param  string  $password     [用户密码]
     * @param  boolean $skipCaptcha  绕过验证码，用于内部登录
     * @param  boolean $skipPassword 绕过密码，用于内部登录
     * @return array  数组格式提示信息
     */
    public function solveUserLogin($username, $password, $locale = null, $skipCaptcha = false, $skipPassword = false)
    {
        if (empty($username) || (empty($password) && !$skipPassword)) {
            return [
                'status' => 0,
                'msg' => __('messages.Login.solveUserLogin.account_password_required'),
            ];
        }

        //$times = intval($this->make('redis')->hget('hlogin_authcode', $userinfo['uid'])) ?: 0;
        //if (!isset($_REQUEST['_m']) && $times >= 5 && !$skipCaptcha && !$this->make('captcha')->Verify($this->make('request')->get('sCode'))) {
        //todo 验证码是否更换
        if (!$skipCaptcha && !Captcha::check(request('captcha'))) {
            return [
                "status" => 0,
                "msg" => __('messages.Login.solveUserLogin.captcha_wrong'),
            ];
        }

        $open_pwd_change = SiteSer::config('pwd_change') ?: false;

        $uid = UserSer::getUidByUsername($username);
        if (!$uid) {
            $uid = UserSer::getUidByNickname($username);
        }

        $member = Users::find($uid);

        $auth = Auth::guard();

        if ($member && $auth->validate(['username' => $username, 'password' => $password])) {
            // freeze check
            if ($member->isFreeze()) {
                $S_qq = Redis::hget('hsite_config:' . SiteSer::siteId(), 'qq_suspend');
                return [
                    'status' => 0,
                    'msg' => __('messages.Login.solveUserLogin.account_block_30days_no_show', ['S_qq' => $S_qq])
                ];
            }

            // platform user check
            if ($member->wrongOrigin()) {
                return ['status' => 0, 'msg' => __('messages.must_login_on_platform')];
            }
        }

        if($open_pwd_change && (!$this->checkPwdChanged($uid))){
            return array(
                'status' => 101,
                'msg' => __('messages.Login.solveUserLogin.password_modify'),
            );
        }

        //取uid
        if (!$auth->attempt([
            'username' => $username,
            'password' => $password,
        ], request('remember'))) {
            return [
                'status' => 0,
                'msg' => __('messages.Login.solveUserLogin.account_password_wrong'),
            ];
        }

        $userAttrService = resolve(UserAttrService::class);

        /* ---用戶locale處理--- */
        if (!empty($locale)) {
            $userAttrService->set($auth->id(), 'locale', $locale);
        }
        /* ---用戶locale處理 end--- */

        app('events')->dispatch(new Login(Auth::user(), true, 11));

        return [
            'status' => 1,
            'msg' => __('messages.Login.solveUserLogin.success'),
            'data'=>[
               Session::getName() => Session::getId(),
            ]
        ];

    }

    private function solveMobileLogin(Request $request)
    {
        $site_id = SiteSer::siteId();
        $redis = resolve('redis');
        $cc_mobile = '';
        $uid = -1;

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

        $open_pwd_change = SiteSer::config('pwd_change') ?: false;

        if ($member = Users::find($uid)) {
            $S_qq = Redis::hget('hsite_config:'.SiteSer::siteId(), 'qq_suspend');
            // freeze check
            if ($member->status==2) {
                return $this->msg(__('messages.Login.solveMobileLogin.account_block_30days_no_show', ['S_qq' => $S_qq]));
            }
            // platform user check
            if ($member->origin >= 50) {
                return $this->msg(['status' => 0, 'msg' => __('messages.must_login_on_platform')]);
            }
        }

        $open_pwd_change = SiteSer::config('pwd_change') ?: false;
        if ($open_pwd_change && (!$this->checkPwdChanged($uid))) {
            return $this->msg(__('messages.Login.solveMobileLogin.password_modify'), 101);
        }

        //取uid
        $auth = Auth::guard();
        $auth->attempt($credentials, request('remember'));

        $resp = [
            'status' => 1,
            'msg' => __('messages.success'),
            'data'=> [
               Session::getName() => Session::getId(),
            ]
        ];
        return JsonResponse::create($resp);
    }

    private function checkPwdChanged($uid){
        $user  = UserSer::getUserByUid($uid);
        if($user && ($user->pwd_change==null || $user->cpwd_time==null)){
            $user = UserSer::getUserReset($uid);
        }
        return $user ? $user['pwd_change'] :1;
    }
    /**
     * 会员注销操作
     *
     * @param Request $request
     * @return Response
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            resolve(RedisCacheService::class)->delSid(Auth::id());
            Auth::logout();
        }
        $request->session()->invalidate();
        // 清除redis
        return JsonResponse::create(['status' => 1, 'msg' => __('messages.Login.solveMobileLogin.is_logout')]);
    }

    /**
     * 双向加密解密方法
     *
     * @param        $string    要加密的字符串
     * @param string $operation 要加密或解密
     * @param string $key       加密解密的秘钥
     * @param int    $expiry
     * @return string
     */
    protected function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {

        $ckey_length = 4;

        $key = md5($key ? $key : SiteSer::config('syn_login_encode_key'));
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = [];
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }
    public function captcha()
    {
        return Captcha::create();
    }

    /**
     * @param $username
     * @return string
     * @Author Orino
     */
    private function remypwdMd5($username)
    {
        $_encrypt_key = SiteSer::config('web_secret_key');
        return md5($username . $_encrypt_key);
    }


}