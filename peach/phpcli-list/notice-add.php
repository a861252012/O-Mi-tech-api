<?php
/**
 * Created by PhpStorm.
 * Date: 15-4-24下午3:46
 * @Author   Orino
 * Descrition:
 */
define('BASEDIR',dirname(__DIR__));
include BASEDIR.'/pdomysql.php';

if( !defined('APP_DIR') ){
    exit('Not Allowed');
}

//$confArr = explode(PHP_EOL,file_get_contents(APP_DIR.'/app/config/parameters.yml'));

$db = pdo();
$_redisInstance = new \Redis();
//$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);
$_redisIsConnected = $_redisInstance->connect($_W['redis']['default']['host'],$_W['redis']['default']['port']);
$redis_password=$_W['redis']['default']['password'];
$_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
if( $_redisIsConnected == false){
    exit('reids is  disconnect!');
}
function  findOneBy($assoc,$field='*',$master=true,$tablename='video_gift_activity'){
    if($master == false){
        $stmt = $GLOBALS['db']->prepare('select '.$field.' from `'.$tablename.'` where moneymin>='.$assoc['column']);//预编译，防止sql注入
    }else{
        $stmt = $GLOBALS['db']->prepare('/*'.MYSQLND_MS_MASTER_SWITCH.'*/select '.$field.' from `'.$tablename.'` where moneymin<='.$assoc['money'].' AND type = 2  and moneymax>'.$assoc['money']);//预编译，防止sql注入
    }
    //$stmt->bindValue(':'.$assoc['column'], $assoc['value']);
    $stmt->execute();
    $stmt = $stmt->fetch(PDO::FETCH_ASSOC);
    return $stmt?$stmt:null;
}

//分组配置，nickname 对应的是昵称，money是充值多少钱，time 是充值时间
$groupArr = array(
    array('nickname'=>'测试一','money'=>100,'time'=> '2015-05-23'),
    array('nickname'=>'测试一','money'=>800,'time'=> '2015-05-23'),
    array('nickname'=>'测试一','money'=>1300,'time'=> '2015-05-23'),
    array('nickname'=>'测试一','money'=>2600,'time'=> '2015-05-23'),
    array('nickname'=>'测试一','money'=>3000,'time'=> '2015-05-23'),
    array('nickname'=>'测试一','money'=>5000,'time'=> '2015-05-23'),
);
foreach($groupArr as $value){
    $giftname = findOneBy(array('money'=>$value['money']),'giftname');
    $_redisInstance->lpush('llast_charge_user2', json_encode(array(
        'adddate'=> date('Y-m-d',strtotime($value['time'])),
        'nickname'=> $value['nickname'],
        'giftname' => $giftname['giftname']
    )));
}
$_redisInstance->ltrim('llast_charge_user2', 0, 19);
echo '执行成功';
