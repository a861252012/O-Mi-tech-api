<?php
define('BASEDIR',realpath('../../'));
date_default_timezone_set("PRC");
//$app = require BASEDIR . '/Vcore/App/app.php';
error_reporting(7);
ini_set('display_errors','On');

$op = isset($_GET['op']) ? $_GET['op'] : false;

session_start();

if($op == 'captcha') {
    error_reporting(0);
    ini_set('display_errors','Off');
    require '../Api/Captcha/SimpleCaptcha.php';
    die;
}
$config = require (BASEDIR.'/Vcore/App/Config/config.php');

if($op == 'register') {
    $skipCaptcha=$config['SKIP_CAPTCHA_REG'];
    if(!$skipCaptcha && (strtolower($_POST['captcha']) != strtolower($_SESSION['CAPTCHA_KEY']))){
        die(json_encode(array(
            "status"=> 0,
            "msg" => "验证码错误!"
        )));
    }

    $username = isset($_REQUEST['username'])?trim($_REQUEST['username']):null;
    if( !preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/',$username) || strlen($username) < 5 || strlen($username) > 30  ){
        die(json_encode(array(
            "status"=> 0,
            "msg" => "注册邮箱不符合格式！(5-30位的邮箱)"
        )));
    }

    $nickname = isset($_REQUEST['nickname'])?trim($_REQUEST['nickname']):null;
    $agent = isset($_COOKIE['agent'])?trim($_COOKIE['agent']):null;
//    $inviteCode = isset($_REQUEST['inviteCode'])?trim($_REQUEST['inviteCode']):null;
    $inviteCode = null; //去除验证码
    //if( strlen($nickname) < 2 || strlen($nickname) > 16 || !preg_match("/^[A-Za-z0-9_".chr(0xa1)."-".chr(0xff)."]+[^_]$/",$nickname)|| !!$this->_findByUserName(array('nickname'=>$nickname)) ){
    $len = sizeof(preg_split("//u", $nickname, -1, PREG_SPLIT_NO_EMPTY));

//    if( !$inviteCode  ){
//        die(json_encode(array(
//            "status"=> 0,
//            "msg" => "邀请码不能为空"
//        )));
//    }

    //昵称不能使用/:;\空格,换行等符号。
    if( $len < 2 || $len > 8 || !preg_match("/^[^\s\/\:;]+$/",$nickname)  ){
        die(json_encode(array(
            "status"=> 0,
            "msg" => "注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)"
        )));
    }

    if(trim($_POST['password1'] != trim($_POST['password2']))){
        die( json_encode(array(
            "status"=> 0,
            "msg" => "两次密码输入不一致!"
        )));
    }

    $password = $_POST['password1'];
    if( strlen($password) < 6 ||  strlen($password) > 22 || preg_match('/^\d{6,22}$/',$password) || !preg_match('/^\w{6,22}$/',$password) ){
        die(json_encode(array(
            "status"=> 0,
            "msg" => "注册密码不符合格式!"
        )));
    }

    $redis = new Redis();
    $redis->connect($config['redis']['default']['host'], $config['redis']['default']['port']);
    $redis->auth($config['redis']['default']['password']);
    if($redis->hExists('husername_to_id', $username)){
        die(json_encode(array(
            "status"=> 0,
            "msg" => "对不起, 该帐号不可用!"
        )));
    }
    if($redis->hExists('hnickname_to_id', $nickname)){
        die(json_encode(array(
            "status"=> 0,
            "msg" => "对不起, 该昵称已被使用!"
        )));
    }

    $newUser = array(
        'did'=>0,
        'username'=>$username,
        'nickname'=>$nickname,
        'password'=>md5($password),
        'pic_total_size'=>524288000,
        'pic_used_size'=>0,
        'created'=>date('Y-m-d H:i:s'),
        'invite_code'=>$inviteCode,
        'agent'=>$agent
    );


    $timestamp = time();

    $token = $config['VFPHP_SIGN'];

    $token = md5($newUser['username'].$token. $newUser['password']);

    $token = md5($token.$timestamp);
    $url = array('timestamp'=>$timestamp, 'token'=>$token);
    $url = array_merge($url, $newUser);


    $register_api = 'http://'.ltrim(preg_replace('/^http:\/\//','',$config['login_domain'][0]),'/').'/api/register?'.http_build_query($url);

    if(!$result = file_get_contents($register_api)){
           echo json_encode(array('status'=>0, 'msg'=>'连接服务器失败'));
    }

    echo $result;

    $result = json_decode($result);
    if($result->status == 1){
        ob_clean();
        echo json_encode($result);
    }
    die;
}

require('html/register.html');