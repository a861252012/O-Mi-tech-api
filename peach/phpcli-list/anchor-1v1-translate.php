<?php
/**
 * Created by PhpStorm.
 * Date: 15-6-12上午10:00
 * @Author   Orino
 * Descrition: 
 */

define('BASEDIR',dirname(__DIR__));
include BASEDIR.'/pdomysql.php';

if( !defined('APP_DIR') ){
    exit('Not Allowed');
}

$db = pdo(true);

$_redisInstance = new \Redis();
//irwin$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);
$_redisIsConnected = $_redisInstance->connect($_W['redis']['default']['host'],$_W['redis']['default']['port']);
$redis_password=$_W['redis']['default']['password'];
$_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
if( $_redisIsConnected == false){
    exit('reids is  disconnect!');
}


//array('column'=>'username','value'=>$username)
function  findBy($assoc,$field='*',$master=true,$tablename='video_user',$single=true,$operator='='){
    if($master == false){
//        irwin$stmt = $GLOBALS['pdo']->prepare('select '.$field.' from `'.$tablename.'` where '.$assoc['column'].$operator.':'.$assoc['column']);//预编译，防止sql注入
        $stmt = $GLOBALS['db']->prepare('select '.$field.' from `'.$tablename.'` where '.$assoc['column'].$operator.':'.$assoc['column']);//预编译，防止sql注入
    }else{
//        irwin$stmt = $GLOBALS['pdo']->prepare('/*'.MYSQLND_MS_MASTER_SWITCH.'*/select '.$field.' from `'.$tablename.'` where roomtid = 4 and status = 0 and '.$assoc['column'].$operator.':'.$assoc['column']);//预编译，防止sql注入
        $stmt = $GLOBALS['db']->prepare('/*'.MYSQLND_MS_MASTER_SWITCH.'*/select '.$field.' from `'.$tablename.'` where roomtid = 4 and status = 0 and '.$assoc['column'].$operator.':'.$assoc['column']);//预编译，防止sql注入
        //  echo '/*'.MYSQLND_MS_MASTER_SWITCH.'*/select '.$field.' from `'.$tablename.'` where '.$assoc['column'].'$operator:'.$assoc['column'];
    }
    $stmt->bindValue(':'.$assoc['column'], $assoc['value']);
    $stmt->execute();
    if($single == true){
        $stmt = $stmt->fetch(PDO::FETCH_ASSOC);
    }else{
        $stmt = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return $stmt?$stmt:null;
}




//先清除老redis数据
$keys = $_redisInstance->keys('hroom_duration:*');
$pipe = $_redisInstance->multi(Redis::PIPELINE);
foreach ($keys as $item )
{
    $pipe->del($item);
}
$pipe->exec();

//将mysql大于今天到7天的预约从mysql读取出来写入到redis
//starttime是开始时间
$data = findBy( array('column'=>'starttime','value'=>date('Y-m-d')) ,
    '*',true,'video_room_duration',false,'>');

if( $data == null){
    return;
}
//按uid分组
$uids =  array_unique(array_column($data, 'uid'));
$pipe = $_redisInstance->multi(Redis::PIPELINE);
//这里只上了type=4的,如果存在多个类型的房间类型，需要处理
foreach( $uids as $item ){
    foreach( $data as $value  ){
        if( $value['uid'] == $item ){
            $id = $value['id'];
            $pipe->hset('hroom_duration:'.$item.':4',$id ,json_encode($value) );
        }
    }
}
$pipe->exec();

$_redisInstance->close();
echo 'All the best!';