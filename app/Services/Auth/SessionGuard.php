<?php

namespace App\Services\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Auth\SessionGuard as Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2016/9/22
 * Time: 9:44
 */
class SessionGuard extends Guard
{
    use GuardHelpers;
    const guard = 'pc';

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
        return !is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  bool $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false)
    {
        /**
         * 7 天免登陆
         */
        if ($remember) {
            //记住我的功能，
            config()->set('session.lifetime', 7 * 24 * 60);
//            Session::save();
        }
        $this->updateSession($user->getAuthIdentifier());

        /**
         * 清除计数 当用户输入正确的登录信息之后要清除掉原来输入错误的次数
         */
        $this->session->remove('hlogin_authcode');

        $this->fireLoginEvent($user, $remember);

        $this->setUser($user);
    }


    /**
     * Update the session with the given ID.
     *
     * @param array $userInfo
     * @param string $huser_sid
     * @return void
     */
    public function writeRedis($userInfo = [], $huser_sid = "")
    {
        try {
            //判断当前用户session是否是最新登陆
            $this->_online = $userInfo['uid'];
            //设置新session
            $this->session->put(self::SEVER_SESS_ID, $userInfo['uid']);           //TODO 只根据session的webonline有无uid判断是否登录成功
            $this->getCookieJar()->queue(self::WEB_UID, $userInfo['uid'], 0);
            $this->repeatLogin($huser_sid);

        } catch (\Exception $e) {
            $this->session->remove(self::SEVER_SESS_ID);
            $this->getCookieJar()->queue(self::WEB_UID, $userInfo['uid'], time() - 31536000);
            $this->_online = false;
            Log::info("用户登录写redis异常：" . $e->getMessage());
        }
    }

    public function repeatLogin($huser_sid)
    {
        $this->_sess_id = $this->session->getId();
        $userInfo['uid'] = $this->session->get(self::SEVER_SESS_ID);
        if (empty($huser_sid)) { //说明以前没登陆过，没必要检查重复登录
            Redis::hset('huser_sid', $userInfo['uid'], $this->_sess_id);
        } elseif ($huser_sid != $this->_sess_id) {//有可能重复登录了
            //更新用户对应的sessid
            Redis::hset('huser_sid', $userInfo['uid'], $this->_sess_id);
            //删除旧session，踢出用户在上一个浏览器的登录状态
            $this->session->getHandler()->destroy($huser_sid);
        }
    }

    /**
     * @param $id
     */
    protected function updateSession($id)
    {
        $this->session->put($this->getName(), $id);
        $this->session->migrate(true);

        $userinfo['uid'] = $id;
        $huser_sid = Redis::hget('huser_sid', $userinfo['uid']);
        $this->writeRedis(['uid' => $userinfo['uid']], $huser_sid);
    }

    public function getName()
    {
        return self::SEVER_SESS_ID;
    }
}