<?php

namespace App\Services\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Auth\SessionGuard as Guard;
use Illuminate\Contracts\Auth\Authenticatable;
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
    const  WEB_SECRET_KEY = 'c5ff645187eb7245d43178f20607920e456';

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
     * @param  bool                                       $remember
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

        $this->fireLoginEvent($user, $remember);

        $this->setUser($user);
    }


    /**
     * @param $id
     */
    protected function updateSession($id)
    {
        $this->session->put($this->getName(), $id);
        $this->session->migrate(true);

        $this->session->put(self::SEVER_SESS_ID, $id);

        $sid = $this->session->getId();

        $huser_sid = Redis::hget('huser_sid', $id);
        if (empty($huser_sid)) { //说明以前没登陆过，没必要检查重复登录
            Redis::hset('huser_sid', $id, $sid);
        } elseif ($huser_sid != $sid) {//有可能重复登录了
            //更新用户对应的sessid
            Redis::hset('huser_sid', $id, $sid);
            //删除旧session，踢出用户在上一个浏览器的登录状态
            $this->session->getHandler()->destroy($huser_sid);
        }
    }

    public function getName()
    {
        return self::SEVER_SESS_ID;
    }
}