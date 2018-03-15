<?php

namespace App\Services\Auth;

use App\Models\Users;
use App\Services\User\UserService;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Session\SessionInterface;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2016/9/22
 * Time: 9:44
 */
class WebAuthService extends SessionGuard
{
    use GuardHelpers;
    const guard = 'pc';

    /** @var  $token Token */
    public $token;
    protected $_config;
    protected $request;

    const CLIENT_ENCRY_FIELD = 'v_remember_encrypt';
    const SEVER_SESS_ID = 'webonline';//在线用户id
    const  TOKEN_CONST = 'auth_key';
    const WEB_UID = 'webuid';
    const  WEB_SECRET_KEY = 'c5ff645187eb7245d43178f20607920e456';
    protected $_online; // 在线用户的uid
    protected $userInfo; // 在线用户的信息
    protected $_reqSession;
    protected $_sess_id;
    protected $flash_url = '';
    protected $flash_version = '';
    public $_isGetCache = false;

    /**
     * Determine if the user matches the credentials.
     *
     * @param  mixed $user
     * @param  array $credentials
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        return !is_null($user) && $this->validateCredentials($user, $credentials);
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminaattemptte\Contracts\Auth\Authenticatable $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials($user, array $credentials)
    {
        $plain = $credentials['password'];
        //具体验证密码有效性方法
        return $user['password'] == md5($plain);
    }

    public function user()
    {
        $uid = Session::get(self::SEVER_SESS_ID);
        $user = resolve('userService')->getUserByUid($uid);
        $this->user = (new Users)->forceFill($user);
        return $this->user;
    }

    /**
     * 登录成功，更新相关数据
     * @param Authenticatable $user
     * @param bool $remember
     * @return bool|Token|void
     */
    public function login(Authenticatable $user, $remember = false)
    {
        $this->updateSession($user['uid']);

        /**
         * 7 天免登陆
         */
        if (Input::get('v_remember')) {
            //记住我的功能，
            config()->set('session.lifetime', 7 * 24 * 60);
            Session::save();
        }

        $this->setUser((new Users)->forceFill($user));
        /**
         * 清除计数 当用户输入正确的登录信息之后要清除掉原来输入错误的次数
         */
        Session::remove('hlogin_authcode');


    }


    /**
     * Update the session with the given ID.
     *
     * @param  string $id
     * @return void
     */
    public function writeRedis($userInfo = [], $huser_sid = "")
    {
        try {
            //判断当前用户session是否是最新登陆
            $this->_online = $userInfo['uid'];
            //设置新session
            session()->put(self::SEVER_SESS_ID, $userInfo['uid']);           //TODO 只根据session的webonline有无uid判断是否登录成功
            setcookie(self::WEB_UID, $userInfo['uid'], 0, '/');//用于首页判断

            $this->repeatLogin($huser_sid);

        } catch (\Exception $e) {
            session()->remove(self::SEVER_SESS_ID);
            setcookie(self::WEB_UID, null, time() - 31536000, '/');
            $this->_online = false;
            \Log::info("用户登录写redis异常：" . $e->getMessage());
        }
    }

    public function repeatLogin($huser_sid)
    {
        $this->_sess_id = Session::getId();
        $userInfo['uid'] = Session::get(self::SEVER_SESS_ID);
        if (empty($huser_sid)) { //说明以前没登陆过，没必要检查重复登录
            app()->make('redis')->hset('huser_sid', $userInfo['uid'], $this->_sess_id);
        } elseif ($huser_sid != $this->_sess_id) {//有可能重复登录了
            //更新用户对应的sessid
            app()->make('redis')->hset('huser_sid', $userInfo['uid'], $this->_sess_id);

            //删除旧session，踢出用户在上一个浏览器的登录状态
            session()->getHandler()->destroy($huser_sid);
        }
    }

    /**
     * @param $id
     */
    protected function updateSession($id)
    {
        $userinfo['uid'] = $id;
        $huser_sid = app()->make('redis')->hget('huser_sid', $userinfo['uid']);
        echo $userinfo['uid'] . ' update session ';
        $this->writeRedis(['uid' => $userinfo['uid']], $huser_sid);
    }

    /**
     * @param array $credentials
     * @param bool $remember
     * @param bool $login
     * @return bool
     */
    public function attempt(array $credentials = array(), $remember = false, $login = true)
    {
        $temp = app(UserService::class)->retrieveByCredentials($credentials);
        $user = (new Users)->forceFill($temp);
        if ($this->hasValidCredentials($user, $credentials) && $user['status']) {
            if ($login) {
                $this->login($user, $remember);
            }
            return true;
        }
        return false;
    }
}