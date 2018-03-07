#!/usr/bin/php
<?php
/**
 * Created by PhpStorm.
 * Date: 15-5-27下午2:13
 * @Author   TX
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
$db = pdo();
function pdoAdd($arr,$tablename){
    $new_arr = array();
    $cols1 = $cols2 = '';
    foreach($arr as $key=>$item){
        $new_arr[':'.$key] = $item;
        $cols1 .= $cols1 ==''?'`'.$key.'`':',`'.$key.'`';
        $cols2 .= $cols2 ==''?':'.$key.'':',:'.$key.'';
    }
    $sql = 'INSERT INTO `'.$tablename.'` ('.$cols1.') VALUES ('.$cols2.')';
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($new_arr);
    return $GLOBALS['pdo']->lastInsertId();
}
$keys = $_redisInstance->getKeys('hroom_duration:*');
foreach( $keys as $item ){
    $roomlist = $_redisInstance->hGetAll($item);
    foreach($roomlist as $room){
        $room = json_decode($room,true);
        $timecheck = date('Y-m-d H:i:s',strtotime($room['starttime']));
        $start = date('Y-m-d H:i:s',time()+150);
        $end = date('Y-m-d H:i:s',time()+450);
        if($start<$timecheck&&$end>$timecheck){
            if( $room['status']==0 &&  $room['reuid']!=0 ){
                pdoAdd(array( 'send_uid'=> 0,
                        'rec_uid'=> $room['uid'],
                        'content'=>'您开设的'.$room['starttime'].'一对一约会房间快要开始了,请做好准备哦',
                        'category'=> 1,
                        'status' => 0,
                        'created'=> date('Y-m-d H:i:s')),'video_mail'
                );
                pdoAdd(array( 'send_uid'=> 0,
                        'rec_uid'=> $room['reuid'],
                        'content'=>'您预约的一对一预约房间'.$room['starttime'].'快要开始了，请做好准备哦',
                        'category'=> 1,
                        'status' => 0,
                        'created'=> date('Y-m-d H:i:s')),'video_mail'
                );
            }
        }
    }
}

