<?php
/**
 * 初始化静态图片
 * Created by PhpStorm.
 * Date: 15-5-15下午4:47
 * @Author   Orino
 * Descrition: 
 */
define('BASEDIR',dirname(__DIR__));
include BASEDIR.'/pdomysql.php';
$_redisInstance = new \Redis();
$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);
$_redisIsConnected = $_redisInstance->connect($_redis_ip_port[0],$_redis_ip_port[1]);
$redis_password=$_W['redis']['default']['password'];
$_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
if( $_redisIsConnected == false){
    exit('reids is  disconnect!');
}
$binimgs = $_redisInstance->keys('shower:cover:*');
$versions = $_redisInstance->keys('shower:cover:version:*');
$binimgs  = array_diff($binimgs,$versions);//求差集
foreach( $binimgs as $item ){
    $uid = intval(str_replace('shower:cover:','',$item));
	if( $uid > 0  ){
   	 $v = $_redisInstance->get('shower:cover:version:'.$uid);
    	if( !!$v  ){
       		 file_put_contents(APP_DIR.'/web/public/images/anchorimg/'.$uid.'_'.$v.'.jpg',$_redisInstance->get('shower:cover:'.$uid)); //初始化静态图片
    	}
	}
}
