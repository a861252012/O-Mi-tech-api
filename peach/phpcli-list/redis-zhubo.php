<?php
/**
 * Created by PhpStorm.
 * @Author   Orino
 * Descrition:
 */
define('BASEDIR', dirname(__DIR__));
include BASEDIR . '/pdomysql.php';

if (!defined('APP_DIR')) {

    exit('Not Allowed');
}
//防止php超时
set_time_limit(0);
$db = pdo();

//uid >= 10000
function  findBy($field = '*', $tablename = 'video_user', $start, $limit)
{
    $stmt = $GLOBALS['db']->prepare('/*' . MYSQLND_MS_MASTER_SWITCH . '*/select ' . $field . ' from `' . $tablename . '` where uid >= 10000 order by uid asc limit ' . $start . ',' . $limit);
    $stmt->execute();
    $stmt = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $stmt ? $stmt : null;
}

$time1 = microtime(true);
echo 'sync start mysql:video_user->' . $time1 . PHP_EOL;
/*$_redisInstance = new \Redis();
$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);//保留下面要用
$_redisIsConnected = $_redisInstance->connect($_redis_ip_port[0],$_redis_ip_port[1]);
$keys = (array)$_redisInstance->keys('huser_info:*');
if( count($keys) > 0 ){
    foreach( $keys as $item ){
        $_redisInstance->del($item);
    }
}*/

$i = 0;
$step = 1000;
//$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);//保留下面要用
$redis_password=$_W['redis']['default']['password'];
while (true) {
    pdo();
     $_redisInstance = new \Redis();
    $_redisIsConnected = $_redisInstance->connect($_W['redis']['default']['host'], $_W['redis']['default']['port']);
    $_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
    if ($i == 0) {
        //清空昵称和用户名对应的uid
        $_redisInstance->DEL('hnickname_to_id');
        $_redisInstance->DEL('husername_to_id');
    }
    $pipe = $_redisInstance->multi(Redis::PIPELINE);
    $zhuBoArr = findBy('*', 'video_user', $i * $step, $step);
    if ($zhuBoArr == null) {
        break;
    }
    foreach ($zhuBoArr as $item) {
        $pipe->hMset('huser_info:' . $item['uid'], $item);
        $pipe->hset('hnickname_to_id', $item['nickname'], $item['uid']);
        $userArr = array();
        $userArr = explode("@", $item['username']);
        $pipe->hset('husername_to_id', (count($userArr) == 2) ? $userArr[0] . "@" . strtolower($userArr[1]) : $item['username'], $item['uid']);
    }
    $pipe->exec();
    $i++;
    $_redisInstance->close();
    $zhuBoArr = $_W['pdo'] = $GLOBALS['pdo'] = null;//防止mysql和redis超时，每次重新生成新句柄再放初始化
}
echo 'elapsed time->' . intval(microtime(true) - $time1) . 's' . PHP_EOL;
echo 'sync end mysql:video_user!' . PHP_EOL;

