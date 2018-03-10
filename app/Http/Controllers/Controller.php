<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


use Illuminate\Support\Facades\Redis;

//use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\Session;
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    const CLIENT_ENCRY_FIELD = 'v_remember_encrypt';
    //const DOMAIN_A = 'domain_a';
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
     * b域名用户登录后，进行的redis的写入，和a域名最大的区别是写入的redis的key是不一样的
     *
     * a : huser_sid_a
     * b ：huser_sid
     *
     * 写入redis
     **/
    public function writeRedis($userInfo, $huser_sid="")
    {
        try {
            //判断当前用户session是否是最新登陆
            $this->_online = $userInfo['uid'];
            //设置新session
            $_SESSION['_sf2_attributes'][self::SEVER_SESS_ID] = $userInfo['uid'];           //TODO 只根据session的webonline有无uid判断是否登录成功
            setcookie(self::WEB_UID, $userInfo['uid'], 0, '/');//用于首页判断
            $this->_sess_id = session_id();

            if (empty($huser_sid)) { //说明以前没登陆过，没必要检查重复登录
                $this->make('redis')->hset('huser_sid', $userInfo['uid'], $this->_sess_id);
            } elseif ($huser_sid != $this->_sess_id) {//有可能重复登录了
                //更新用户对应的sessid
                $this->make('redis')->hset('huser_sid', $userInfo['uid'], $this->_sess_id);
                //删除旧session，踢出用户在上一个浏览器的登录状态
                $this->make('redis')->del('PHPREDIS_SESSION:' . $huser_sid);
            }
            if ($this->_isGetCache == false) {
                $userArr = array();
                $userArr = explode("@", $userInfo['username']);
                $this->make('redis')->hset('husername_to_id', (count($userArr) == 2) ? $userArr[0] . "@" . strtolower($userArr[1]) : $userInfo['username'], $userInfo['uid']);
                $this->make('redis')->hset('hnickname_to_id', $userInfo['nickname'], $userInfo['uid']);
                $this->make('redis')->hmset('huser_info:' . $userInfo['uid'], $userInfo);
            }
            $this->_points = $userInfo['points'];
        } catch (\Exception $e) {
            unset($_SESSION['_sf2_attributes'][self::SEVER_SESS_ID]);
            setcookie(self::WEB_UID, null, time() - 31536000, '/', $GLOBALS['CUR_DOMAIN']);
            $this->_online = false;
            $logPath = BASEDIR . '/app/logs/business_' . date('Y-m-d') . '.log';
            $this->logResult("用户登录写redis异常：".$e->getMessage(),$logPath);
        }
    }

    /**
     * @param string $name
     * @return \Illuminate\Redis\Connections\Connection|null
     */
    public function make($name=""){
        $service = null;
        switch ($name){
            case 'redis':
                $service = Redis::resolve();
                break;
            case 'captcha':

                break;
            case "userServer":
               // $service = app()->make(UserService::class);
                break;
        }
        return $service;
    }

    /**
     */
    public function request(){
        $request = Request::createFromGlobals();
        $request->setSession(new Session());
        return $request;
    }
    /**
     * * 全站注册/登录密码解密函数
     * @param $s
     * @return string
     * @author D.C
     * @update 2015-02-04
     */
    public function decode($s)
    {
        $a = str_split($s, 2);
        $s = '%' . implode('%', $a);
        $s = urldecode($s);
        return trim($s);
    }
}
