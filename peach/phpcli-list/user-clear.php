<?php
/**
 * Created by PhpStorm.
 * @Author   Orino
 * Descrition:
 */
//
//echo $argc . "\n";
//var_dump($argv); die;
define('BASEDIR', __DIR__ . '/..');
if ($argc < 2) {
    echo "缺少参数，请重新输入，1用户，2主播\n";
    die;
}
date_default_timezone_set('Asia/Shanghai');
require __DIR__ . '/../pdomysql.php';
if (!defined('APP_DIR')) {
    exit('Not Allowed');
}
//防止php超时
set_time_limit(0);


$time1 = microtime(true);
echo 'sync start mysql:video_user->' . $time1 . PHP_EOL;
//
$_redis_ip_port = explode(':', $_W['REDIS_CLI_IP_PORT']);//保留下面要用
$redis_password = $_W['redis']['default']['password'];
$i = 0;
$step = 1000;

//清理测试类主播账号，已离开两个月的主播账号冻结--------------
//测试类用户清理，未充值并且二个月未登录的账号冻结------------
$str = "";
if ($argv[1] == 1) {
    $date = date('Y-m-d H:i:s', strtotime('-2 month'));
    echo $str = "禁闭未充值且【" . $date . "】之前未登陆的用户\n";
    $sql = "SELECT * FROM video_user where roled=0 AND logined<'" . $date . "' and uid NOT IN(select DISTINCT(uid) FROM video_recharge where  pay_type in (1,4,7)
        and pay_status=2)";
} elseif ($argv[1] == 2) {
    $date = date('Y-m-d H:i:s', strtotime('-2 month'));
    echo $str = "禁闭【" . $date . "】之前未登陆的主播\n";
    //IFNULL(last_play_date,created)< DATE_ADD(now(), interval -2 month);
    $sql = "select * from video_user where roled=3 and IFNULL(last_play_date,created)< '".$date."'";
} else {
    die('参数错误');
}
$logStr = date('Y-m-d H:i:s') . " start " . $str . "\n";
file_put_contents(APP_DIR . '/app/logs/clear-user-list.log', $logStr . PHP_EOL, FILE_APPEND);
$_redisInstance = new \Redis();
while (true) {
    //$start = $i * $step;
    $start = 0;
    $temp_sql = $sql . " LIMIT $start,$step;";
    $data = pdo_fetchall($temp_sql);
    if (empty($data)) break;
    if (!isset($data[0])) {//只取到一条数据结构可能改变
        $data = [$data];
    }
    $uidStr = implode(',', array_column($data, 'uid'));
    $delMount = "UPDATE video_user SET status=0,rid=0,roled=0 where uid IN ($uidStr);";
    $modifiedRows = pdo_query($delMount);
    $logStr = "     " . $uidStr . "\n";
    $logStr = $logStr . '-------------expect modified row:' . count($data) . ' modified:' . $modifiedRows . PHP_EOL;
    file_put_contents(APP_DIR . '/app/logs/clear-user-list.log', $logStr . PHP_EOL, FILE_APPEND);

    $_redisIsConnected = $_redisInstance->pconnect($_redis_ip_port[0], $_redis_ip_port[1]);
    $_redisIsConnected = $_redisIsConnected && $_redisInstance->auth($redis_password);
    $pipe = $_redisInstance->multi(Redis::PIPELINE);
    foreach ($data as $k => &$v) {
        $pipe
            ->hDel('husername_to_id', $v['username'])
            ->hDel('hnickname_to_id', $v['nickname']);
        //为保持数据完整，避免脏数据，用户主播都尝试清理主播redis
//        if ($argv[1]=2){
        $pipe->hdel('hroom_ids', $v['uid'])
            ->del('hvediosKtv:' . $v['uid'])
            ->del('suservideo_tag:' . $v['uid'])
            ->srem('svideo_tag:1', $v['uid'])
            ->srem('svideo_tag:2', $v['uid'])
            ->srem('svideo_tag:3', $v['uid'])
            ->del('hroom_status:' . $v['uid'] . ':1')
            ->del('hroom_status:' . $v['uid'] . ':2')
            ->del('hroom_status:' . $v['uid'] . ':3')
            ->del('hroom_status:' . $v['uid'] . ':4')
            ->del('hroom_status:' . $v['uid'] . ':5')
            ->del('hroom_status:' . $v['uid'] . ':6')
            ->del('hroom_status:' . $v['uid'] . ':7')
            ->del('hvedios_ktv_set:' . $v['uid'])
//            ->del('zuser_byattens:' . $v['uid'])
//            ->del('zrange_gift_history:' . $v['uid'])
        ;
            $pipe->hset('huser_ban_uid', $v['uid'],$v['username'])
                ->hset('huser_ban_username', $v['username'],$v['uid']);
//        }
    }
    $pipe->exec();
    $_redisInstance->close();
    $i++;

    $data = $_W['pdo'] = $GLOBALS['pdo'] = null;//防止mysql和redis超时，每次重新生成新句柄再放初始化
    usleep(500);
}
$logStr = date('Y-m-d H:i:s') . " end" . "\n";
file_put_contents(APP_DIR . '/app/logs/clear-user-list.log', $logStr . PHP_EOL, FILE_APPEND);

echo 'elapsed time->' . intval(microtime(true) - $time1) . 's' . PHP_EOL;
echo 'sync end mysql:video_user!' . PHP_EOL;

