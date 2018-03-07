<?php
/**
 * Created by PhpStorm.
 * Date: 15-5-18上午10:15
 * @Author   Orino
 * Descrition: 
 */
include dirname(__FILE__).'/apicommon.php';
if(   !defined('APP_DIR')  ){
    exit(json_encode(array(
        "status"=> 0,
        "msg" => "非法提交数据！"
    )));
}
header('Content-Type: application/json;Charset=utf-8');
$confArr = explode(PHP_EOL,file_get_contents(APP_DIR.'/app/config/parameters.yml'));
$_W = array();
foreach( $confArr as $item){
    if( strpos($item,': ') > 0){
        $item = explode(': ',$item);
        $_W[str_replace(array(' ',PHP_EOL),array('',''),$item[0])] = $item[1];
    }
}
unset($confArr);
$_redisInstance = new \Redis();
$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);
$_redisIsConnected = $_redisInstance->connect($_redis_ip_port[0],$_redis_ip_port[1]);
$redis_password=$_W['redis']['default']['password'];
$_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
$flashVer = $_redisInstance->get('flash_version');
!$flashVer && $flashVer = 'v201504092044';
$flashVer  = $_redisInstance->get('home_js_data_'.$flashVer);
if( !$flashVer ) return;
$flashVer = str_replace(array('cb(',');'),array('',''),$flashVer);
exit($flashVer);