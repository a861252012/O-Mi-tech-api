#!/usr/bin/php
<?php
/**
 * Created by PhpStorm.
 * Date: 15-5-20下午5:21
 * @Author   Orino
 * Descrition: 
 */

define('BASEDIR',dirname(__DIR__));
include BASEDIR.'/pdomysql.php';

$filename =  APP_DIR.'/app/logs/firstcharge_error_'.date('Y-m-d').'.log';
if( !file_exists($filename) ){
   return;
}
$arr = explode(PHP_EOL, file_get_contents($filename) );
$arr = array_filter( $arr);
if( empty($arr) ) return;
function curl_http($activityPostData){
    global $_W ;
   /* $activityPostData = array(
        'ctype' => $activityName, //活动类型
        'money'=> $money, //充值的金额
        'uid'=>   $uid, //用户id
        'token' => $token, //口令牌
        'order_num' => $tradeno, //定单号
    );*/
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$_W['VFPHP_HOST_NAME'].$_W['ACTIVITY_URL']);
    curl_setopt($ch,CURLOPT_POST, 1);
    curl_setopt($ch,CURLOPT_POSTFIELDS, trim($activityPostData).'&vsign='.$_W['VFPHP_SIGN'] );//$activityPostData已经是k1=v2&k2=v2的字符串
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,2);
    curl_setopt($ch,CURLOPT_TIMEOUT, 3);
    curl_exec($ch);
   //$res= curl_exec($ch);
    curl_close ($ch);
}
//处理后清空log的内容
file_put_contents($filename,'');
foreach( $arr as $item ){
    curl_http($item);
}
