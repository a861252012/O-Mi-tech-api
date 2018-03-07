#! /usr/bin/php
<?php
/**
 * Created by PhpStorm.
 * Date: 15-6-12上午11:55
 * @Author   Orino
 * Descrition: 
 */

define('BASEDIR',dirname(__DIR__));
include BASEDIR.'/pdomysql.php';
//注册一个自动关闭链接的函数
function vshutdown()
{
    global $_redisInstance;
    if( $_redisInstance != null ){
        $_redisInstance->close();//自动关闭redis链接
    }
}
register_shutdown_function('vshutdown');
$db = pdo();
$_redisInstance = new \Redis();
$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);
$_redisIsConnected = $_redisInstance->connect($_redis_ip_port[0],$_redis_ip_port[1]);
$redis_password=$_W['redis']['default']['password'];
$_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
if( $_redisIsConnected == false){
    exit('reids is  disconnect!'.PHP_EOL);
}
$flashVer = $_redisInstance->get('flash_version');
!$flashVer && $flashVer = 'v201504092044';
//home_all_,home_rec_,home_ord_,home_gen_,home_vip_
$conf_arr = array(
    'home_all_'=> array('所有主播','all'),
    'home_rec_'=> array( '小编推荐','rec'),
    'home_ord_'=> array('一对一房间','ord'),
    'home_gen_'=> array('才艺主播','gen'),
    //'home_vip_'=> array('会员专区','vip'),
    'home_mobile_'=> array('手机直播','mobile'),
);
//$json = '{';
foreach( $conf_arr as $key=>$item ){
    $data =  $_redisInstance->get($key.$flashVer);
    if( $data == null ){
        echo $item[0].'可能出问题了，请联系java开发人员'.PHP_EOL;
        file_put_contents(APP_DIR.'/web/videolist'.$item[1].'.json','{"rooms":[]}');
    }else{
       // $json .= $item[1].':'.$data;
        $data = str_replace(array('cb(',');'),array('',''),$data);
        file_put_contents(APP_DIR.'/web/videolist'.$item[1].'.json',$data);
    }
}
//$json .= '}';
//file_put_contents(APP_DIR.'/web/videolistmore.json',$json);
