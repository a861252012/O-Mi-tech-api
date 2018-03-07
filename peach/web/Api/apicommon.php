<?php
/**
 * Created by PhpStorm.
 * Date: 15-1-1上午10:50
 * @Author   Orino
 * Descrition: 
 */
//header('Content-type:text/html;Charset=utf-8');
//error_reporting(E_ALL ^ E_NOTICE);
//error_reporting(E_ALL);
error_reporting(0);
!defined('APP_DIR') && define('APP_DIR',dirname(__FILE__).'/../../');
$_W = include APP_DIR.'/app/cache/cli-files/php-conf-cache.php';
try{
    $domain = explode('.',$_SERVER['HTTP_HOST']);
    $len = count($domain);
    $_W['v_remember_encrypt'] = '.'.$domain[$len-2].'.'.$domain[$len-1];//将domain注册到配置文件
    ini_set('session.cookie_domain',  $_W['v_remember_encrypt']);//限制二级域名;
    session_start();
}catch (Exception $e){
    exit(json_encode(array(
        "status"=> 0,
        "msg" => "网络繁忙！"
    )));
}


/**
 * @param $capCode
 * @return bool true通过验证 false不通过验证
 * @Author Orino
 */
function checkCaptcha($capCode,$codeStatus=1){
    if( $codeStatus == 0 ){
        return true;
    }
    if( !$capCode || !isset($_SESSION['CAPTCHA_KEY']) || strtolower($capCode) != $_SESSION['CAPTCHA_KEY'] ){
        return false;
    }
    return true;
}


/**
 * * 全站注册/登录密码解密函数
 * @param $s
 * @return string
 * @author D.C
 * @update 2015-02-04
 */
function decode($s){
    $a = str_split($s,2);
    $s = '%' . implode('%',$a);
    $s = urldecode($s);
    return trim($s);
}