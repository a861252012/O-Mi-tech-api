#!/usr/bin/php
<?php
include dirname(__FILE__) . '/../pdomysql.php';
$_redisInstance = new \Redis();
$_redisIsConnected = $_redisInstance->connect($_W['redis']['default']['host'],$_W['redis']['default']['port']);
$redis_password=$_W['redis']['default']['password'];
$_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
if ($_redisIsConnected == false) {
    exit('reids is  disconnect!');
}
$db = pdo();
function pdoAdd($arr, $tablename)
{
    $new_arr = array();
    $cols1 = $cols2 = '';
    foreach ($arr as $key => $item) {
        $new_arr[':' . $key] = $item;
        $cols1 .= $cols1 == '' ? '`' . $key . '`' : ',`' . $key . '`';
        $cols2 .= $cols2 == '' ? ':' . $key . '' : ',:' . $key . '';
    }
    $sql = 'INSERT INTO `' . $tablename . '` (' . $cols1 . ') VALUES (' . $cols2 . ')';
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($new_arr);
    return $GLOBALS['pdo']->lastInsertId();
}

$keys = $_redisInstance->getKeys('hbuy_one_to_more:*');
//一个主播只发一次消息
$zb_msg_sent = [];
foreach ($keys as $item) {
    $room = $_redisInstance->hGetAll($item);
    $timecheck = date('Y-m-d H:i:s', strtotime($room['starttime']));
    $start = date('Y-m-d H:i:s', time() + 150);
    $end = date('Y-m-d H:i:s', time() + 450);
    if ($start < $timecheck && $end > $timecheck) {
        if (1) {
            if (!in_array($room['rid'], $zb_msg_sent)) {//一个主播只发一次消息
                pdoAdd(array('send_uid' => 0,
                    'rec_uid' => $room['rid'],
                    'content' => '您开设的' . $room['starttime'] . '一对多约会房间快要开始了,请做好准备哦',
                    'category' => 1,
                    'status' => 0,
                    'created' => date('Y-m-d H:i:s')), 'video_mail'
                );
                $zb_msg_sent[] = $room['rid'];
            }

            pdoAdd(array('send_uid' => 0,
                'rec_uid' => $room['uid'],
                'content' => '您预约的一对多房间，5分钟后开启，赶快进入直播间吧！',
                'category' => 1,
                'status' => 0,
                'created' => date('Y-m-d H:i:s')), 'video_mail'
            );
        }
    }
}

