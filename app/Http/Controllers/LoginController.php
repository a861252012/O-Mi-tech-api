<?php

namespace App\Http\Controllers;

use App\Models\UserDomain;
use App\Models\UserLoginLog;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;


class LoginController extends Controller
{

    /**
     * [登录页面进入] 主域名登录使用的页面
     *
     *  <p>
     *  登录域：A
     *         在配置文件中配置 login_domain选项，跳转时会随机一个域名
     *  用户所属域：B
     *              用户所属的域名是在后台添加用户时进行分配的
     *
     *  登录流程：
     *  》当用户访问网站时，检测未登录，会定向到A的passport方法进行登录；
     *  》用户AJAX提交登录信息，验证通过后，首先进行A的登录，同时根据用户信息加密生成通知B域名该用户登录的synstr并返回到页面；
     *  》A登录页面在拿到synstr后，js发起请求到B实现用户在B域名也同时登录了，js发起请求的目的是通过浏览器实现cookie和sid的绑定;
     *  》跳转到B。
     *
     *  登录后Redis中记录session的KEY：
     *  A；huser_sid_a uid session_id (redis hash)
     *  B；huser_sid uid session_id  (redis hash)
     *
     *  登录后的会话保持会有四种现象：
     *  1、A 在线  B 在线 > 正常状态
     *  2、A 在线  B 退出 > A会重新通知B登录,B的reloadLogin方法
     *  3、A 退出  B 在线 > 用户访问A登录页面时，用户在A重新输入信息登录，会刷新B的会话信息
     *  4、A 退出  B 退出 > 重新登录
     *
     *     |------------------|                                                   |-------------------|
     *     |            login | 1登录成功后页面中AJAX 通知B域名synLogin方法登录>  |  synlogin         |
     *     | A                | 2当B退出后跳到A时，A在线，reload方法重新让B登录>  |  reload         B |
     *     |                  |                                                   |                   |
     *     |__________________|                                                   |__________________ |
     * </p>
     *
     * @author
     * @return object
     *
     * TODO 去除登录页面---可以去除
     */
    public function passport()
    {
        return new RedirectResponse('/?handle=login');
        //增加判断是否登出时删除A域名cookie
        $code = $this->make('request')->get('code');
        parse_str($this->authcode($code, 'DECODE', $this->container->config['config.syn_login_encode_key']), $get);
        $remember = 0;
        $action = "";
        if (empty($get)) {

        } else {

            $remember = $get['v_remember'];
            $action = $this->make('request')->get('action');
            if (isset($remember) && isset($action) && strcmp($remember, '1') == 0 && strcmp($action, 'logout') == 0) {
                //这里清除A站的cookie
                // 检查cookie
                $this->_clearCookie();
                $this->make('request')->getSession()->invalidate();//销毁session,生成新的sesssID
                return new RedirectResponse('/passport', 301);
            }
        }


        if ($this->checkLogin()) {
            $redirect = UserDomain::find($this->userInfo['did']);

            /**
             * 取出a域名上是否有记录几天登录的cookie，
             * 有就传递到b域名上
             */
            $_reqCookie = $this->request()->cookies->all();
            $remember = 0;
            if ($_reqCookie && isset($_reqCookie[self::CLIENT_ENCRY_FIELD]) && $_reqCookie[self::CLIENT_ENCRY_FIELD] != null) {
                $remember = 1;
            }
            $time = time();

            /**
             * 请求的是reload方法，用于重新刷新登录数据的操作
             */
            $domainA = $_SERVER['HTTP_HOST'];
            $code = $this->authcode('username=' . $this->userInfo['username'] . '&uid=' . $this->userInfo['uid'] . '&time=' . $time . '&v_remember=' . $remember, 'ENCODE', $this->container->config['config.syn_login_encode_key']);
            $synstr = 'http://' . $redirect['domain'] . '/reload?time=' . $time . '&code=' . urlencode($code) . '&domainA=' . $domainA;

            return new RedirectResponse($synstr);
        }
        return $this->render('Login/passport', array());
    }

    /**
     * a域名执行一站后台导入用户注册
     * <p>
     * 此方法只用于在主域名中导入用户注册的具体方法，用户域名是限制走此方法的
     * http://www.52mitaoer.com/api/register?username=&password&extends=&token=
     * </p>
     * @return RedirectResponse|Response
     */
    public function mitaoReg()
    {
        //接收报文数据
        $_POST = $this->make('request');
        if ($_POST) {
            $username = $_POST->get('username') ? $_POST->get('username') : '';
            $password = $_POST->get('password') ? $_POST->get('password') : '';
            $extends = $_POST->get('extends') ? $_POST->get('extends') : '';

            if (isset($username, $password, $extends)) {
            } else {
                $res = array(
                    'status' => 1,
                    'msg' => '用户信息不全'
                );
                return new JsonResponse($res);
            }

            $extendsArr = json_decode($extends, 1);
            $nickname = $extendsArr['nickname'];
            if (!isset($nickname)) {
                $res = array(
                    'status' => 1,
                    'msg' => '用户信息不全'
                );
                return new JsonResponse($res);
            }

            $user = Users::where('username', $username)->first();
            //->fields(array('uid', 'username'))->eq(array('username' => trim($_POST['username'])))->getOne();
            if ($user['uid']) {
                $res = array(
                    'status' => 1,
                    'msg' => '对不起！该邮件地址已存在'
                );
                return new JsonResponse($res);
            }

            //$user = $this->_frontuserModel->fields(array('nickname'))->eq(array('nickname' => trim($_POST['nickname'])))->getOne();
            if ($user['nickname']) {
                $res = array(
                    'status' => 1,
                    'msg' => '对不起！该昵称已被使用'
                );
                return new JsonResponse($res);
            }

            $user = array(
                'did' => 1,
                'username' => trim($username),
                'password' => md5(trim($password)),
                'nickname' => trim($nickname),
                'sex' => 1,
                'exp' => 0,
                'points' => 0,
                'created' => date('Y-m-d H:i:s'),
                'rich' => 0,
                'pop' => 0,
                'status' => 1,
            );
            $res = Users::create($user);
            if ($res) {
                $user = Users::where('username', trim($username))->first();
                $uid = $user['uid'];
                $created = $user['created'];
                $userR = array(
                    'uid' => $uid,
                    'did' => 1,
                    'username' => trim($username),
                    'password' => md5(trim($password)),
                    'nickname' => trim($nickname),
                    'sex' => 1,
                    'exp' => 0,
                    'points' => 0,
                    'created' => $created,
                    'rich' => 0,
                    'pop' => 0,
                    'status' => 1,
                );
                $this->make('redis')->hmset("huser_info:" . $uid, $userR);
//                $redis->hMset("huser_info:$uid", $user);

                $userArr = explode("@", $user['username']);
                $this->make('redis')->hset('husername_to_id', (count($userArr) == 2) ? $userArr[0] . "@" . strtolower($userArr[1]) : $user['username'], $uid);
                $this->make('redis')->hset('hnickname_to_id', $user['nickname'], $uid);
//                $redis->hset('husername_to_id', (count($userArr) == 2) ? $userArr[0] . "@" . strtolower($userArr[1]) : $user['username'], $uid);
//                $redis->hset('hnickname_to_id', $user['nickname'], $uid);

                $res = UserDomain::where('id', 1)->increment('users', 1);
                if (!$res) {
                    $res = array(
                        'status' => 1,
                        'msg' => '更新域名失败'
                    );
                    return new JsonResponse($res);
                }
//                    $sql = "UPDATE video_user_domain SET users = users+1 WHERE id=" . $_POST['did'];
//                    $this->_frontUserDomain->execute($sql);

                $res = array(
                    'status' => 0,
                    'msg' => '注册成功'
                );
                return new JsonResponse($res);
            } else {
                $res = array(
                    'status' => 1,
                    'msg' => '用户注册失败'
                );
                return new JsonResponse($res);
            }
        } else {
            $res = array(
                'status' => 1,
                'msg' => '未收到用户数据'
            );
            return new JsonResponse($res);
        }


    }

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
        //获取值
        $user_name = $request->get('uname') ?: '';
        $password = $request->get('password') ?: '';
        if (!isset($_REQUEST['_m'])) {
            $password = $this->decode($password); // 密码传递解密
        }
        $retval = $this->solveUserLogin($user_name, $password, config('app.SKIP_CAPTCHA_LOGIN'));


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
//        $domain = $this->container->config['config.login_domain'];
//        $login_domain = array_rand($this->container->config['config.login_domain'], 1);
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
            $res = array(
                'status' => 1,
                'msg' => $this->_sess_id,
                'uid' => $this->_online
            );
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
        parse_str($this->authcode($code, 'DECODE', $this->container->config['config.syn_login_encode_key']), $get);

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
        $huser_sid = $this->make('redis')->hget('huser_sid', $uid);
        // 此时调用的是单实例登录的session 验证
        $this->writeRedis($member->toArray(), $huser_sid);

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
                self::CLIENT_ENCRY_FIELD, $this->_online . '|' . $this->remypwdMd5($this->login_user['username']) . '|' . $domainA,
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
        return new Response(json_encode(array(
            'ret' => $this->checkLogin()
        )));
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
     * @param  string $username [用户名称]
     * @param  string $password [用户密码]
     * @param  boolean $skipCaptcha 绕过验证码，用于内部登录
     * @param  boolean $skipPassword 绕过密码，用于内部登录
     * @return array  数组格式提示信息
     */
    public function solveUserLogin($username, $password, $skipCaptcha = false, $skipPassword = false)
    {
        if (empty($username) || (empty($password) && !$skipPassword)) {
            return array(
                'status' => 0,
                'msg' => '用户名或密码不能为空'
            );
        }

        //$times = intval($this->make('redis')->hget('hlogin_authcode', $userinfo['uid'])) ?: 0;
        //if (!isset($_REQUEST['_m']) && $times >= 5 && !$skipCaptcha && !$this->make('captcha')->Verify($this->make('request')->get('sCode'))) {
        $times = 0;
        //todo 验证码是否更换
        if (!isset($_REQUEST['_m']) && !$skipCaptcha && !$this->make('captcha')->Verify($this->make('request')->get('sCode'))) {
            return array(
                "status" => 0,
                "msg" => "验证码错误，请重新输入！",
                "failNums" => $times
            );
        }

        //取uid
        $auth = Auth::guard();
        if (!$auth->attempt([
            'username' => $username,
            'password' => $password,
        ], request('v_remember'))) {
            return array(
                'status' => 0,
                'msg' => '您的账号登录失败，请联系客服！',
            );
        };
        /**
         * 记录最后的登录ip地址
         */
        $uid = $auth->id();
        $login_ip = $this->request()->getClientIp();
        $user = Users::find($uid);
        $user->last_ip = $login_ip; // 最后登录ip TODO 大流量优化，目前没压力
        $user->logined = date('Y-m-d H:i:s'); // 最后登录时间
        $user->save();
        //记录登录日志
        $this->loginLog($uid, $login_ip, date('Y-m-d H:i:s'));
        return array(
            'status' => 1,
            'msg' => '登录成功',
            'sid'=>Session::getId()
        );
    }

    /**
     * 会员注销操作
     *
     * @return Response
     */
    public function logout()
    {
        if (!$this->checkLogin()) {
            return new RedirectResponse('/');
        }
        // 从cookie里面获取A域名地址irwin
        $_reqCookie = $this->request()->cookies->all();

        // 删除B域名上的session 踢出用户
        $sid = $this->make('redis')->hget('huser_sid', $this->_online);
        $this->make('redis')->del('PHPREDIS_SESSION:' . $sid);

        // 检查cookie
        $this->_clearCookie();
        $this->make('request')->getSession()->invalidate();//销毁session,生成新的sesssID

        if ($_reqCookie && isset($_reqCookie[self::CLIENT_ENCRY_FIELD]) && $_reqCookie[self::CLIENT_ENCRY_FIELD] != null) {
            // A域名取数组第三个值
            $cookiestr = explode('|', $_reqCookie[self::CLIENT_ENCRY_FIELD]);

            if ((count($cookiestr) != 3) || !isset($cookiestr[2])) {
                return $this->_online;
            }
            return new RedirectResponse('/');
        }

        // 清除redis
        return new RedirectResponse('/');
    }

    /**
     * 双向加密解密方法
     *
     * @param $string 要加密的字符串
     * @param string $operation 要加密或解密
     * @param string $key 加密解密的秘钥
     * @param int $expiry
     * @return string
     */
    protected function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {

        $ckey_length = 4;

        $key = md5($key ? $key : $this->container->config['config.syn_login_encode_key']);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
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


    /**
     * @param $username
     * @return string
     * @Author Orino
     */
    private function remypwdMd5($username)
    {
        $_encrypt_key = $this->container->config['config.WEB_SECRET_KEY'];
        return md5($username . $_encrypt_key);
    }


    public function loginLog($uid, $login_ip, $date)
    {
        return UserLoginLog::create([
            'uid' => $uid,
            'ip' => $login_ip,
            'created_at' => $date,
        ]);
    }


}