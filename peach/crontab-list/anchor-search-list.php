#! /usr/bin/php
<?php
/**
 * 缓存主播的数据
 * Created by PhpStorm.
 * Date: 15-5-26下午6:12
 * @Author   Orino
 * Descrition:
 */

define('BASEDIR', dirname(__DIR__));
include BASEDIR . '/pdomysql.php';
$db = pdo();
$_redisInstance = new \Redis();
//irwin$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);
$_redisIsConnected = $_redisInstance->connect($_W['redis']['default']['host'], $_W['redis']['default']['port']);
$redis_password=$_W['redis']['default']['password'];
$_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
if ($_redisIsConnected == false) {
    exit('reids is  disconnect!');
}

$flashVer = $_redisInstance->get('flash_version');
!$flashVer && $flashVer = 'v201504092044';
//home_all_,home_rec_,home_ord_,home_gen_,home_vip_
$conf_arr = array(
    'home_all_' => array('所有主播', 'all'),
    'home_rec_' => array('小编推荐', 'rec'),
    'home_ord_' => array('一对一房间', 'ord'),
    'home_gen_' => array('才艺主播', 'gen'),
    //'home_vip_' => array('会员专区', 'vip'),
);
//$json = '{';
foreach ($conf_arr as $key => $item) {
    $data = $_redisInstance->get($key . $flashVer);
    if ($key = 'home_all_') {
        $data = str_replace(array('cb(', ');'), array('', ''), $data);
        $myfav = json_decode($data, true);
        break;
    }
}

if ($myfav) {
    $myfav_arr = array();
    foreach ($myfav['rooms'] as $item) {
        $myfav_arr[$item['username']] = $item;
    }
}

function make_comparer()
{
    // Normalize criteria up front so that the comparer finds everything tidy
    $criteria = func_get_args();
    foreach ($criteria as $index => $criterion) {
        $criteria[$index] = is_array($criterion)
            ? array_pad($criterion, 3, null)
            : array($criterion, SORT_ASC, null);
    }

    return function ($first, $second) use (&$criteria) {
        foreach ($criteria as $criterion) {
            // How will we compare this round?
            list($column, $sortOrder, $projection) = $criterion;
            $sortOrder = $sortOrder === SORT_DESC ? -1 : 1;

            // If a projection was defined project the values now
            if ($projection) {
                $lhs = call_user_func($projection, $first[$column]);
                $rhs = call_user_func($projection, $second[$column]);
            } else {
                $lhs = $first[$column];
                $rhs = $second[$column];
            }

            // Do the actual comparison; do not return if equal
            if ($lhs < $rhs) {
                return -1 * $sortOrder;
            } else if ($lhs > $rhs) {
                return 1 * $sortOrder;
            }
        }

        return 0; // tiebreakers exhausted, so $first == $second
    };
}
usort($myfav_arr, make_comparer(['live_status', SORT_DESC], ['attens', SORT_DESC], ['lv_exp', SORT_DESC]));

file_put_contents(BASEDIR . '/app/cache/cli-files/anchor-search-data.php',
    '<?php ' . PHP_EOL . 'return ' . preg_replace('/[\s' . PHP_EOL . ']+/m', '', var_export($myfav_arr, true)) . ';'
);
