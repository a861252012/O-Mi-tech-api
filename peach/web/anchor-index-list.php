<?php

// #!/usr/bin/php

/**
 * Created by PhpStorm.
 * Date: 15-5-18上午10:15
 * @Author   Orino
 * Descrition:
 */


include dirname(__FILE__).'/../pdomysql.php';
$_redisInstance = new \Redis();
$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);
$_redisIsConnected = $_redisInstance->connect($_redis_ip_port[0],$_redis_ip_port[1]);
$redis_password=$_W['redis']['default']['password'];
$_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
if( $_redisIsConnected == false){
    exit('reids is  disconnect!');
}
$flashVer = $_redisInstance->get('flash_version');
!$flashVer && $flashVer = 'v201504092044';
$flashVer  = $_redisInstance->get('home_js_data_'.$flashVer);
if( !$flashVer ) return;
$flashVer = str_replace(array('cb(',');'),array('',''),$flashVer);
echo $flashVer;
//file_put_contents(dirname(__FILE__).'/../web/videolist.json',$flashVer);
