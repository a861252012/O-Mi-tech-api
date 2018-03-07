<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2016/10/7
 * Time: 15:24
 */
define('BASEDIR',dirname(__DIR__));
$configCache=require __DIR__.'/../Vcore/App/Config/config.php';
$_redis_ip_port = explode(':',$configCache['REDIS_CLI_IP_PORT']);
$db = $configCache['database']['connections']['mysql'];
$config = (object)[
    'database_host' => $db['host'],
    'database_port' => $db['port'],
    'database_name' => $db['database'],
    'database_user' => $db['username'],
    'database_password' => $db['password'],
    'REDIS_IP' => $_redis_ip_port[0],
    'REDIS_PORT' => $_redis_ip_port[1],
];
//var_dump($config);
$redis = new \Redis();
try {
    header('Content-type: application/json');
    $redis->connect($config->REDIS_IP, $config->REDIS_PORT);
    $redis->auth($configCache['redis']['default']['password']);
    $domainList = $redis->get('domain:list');
    if ($domainList) {
        echo $domainList;
        $redis->close();
        exit;
    }
    $db = new PDO("mysql:host=$config->database_host;port=$config->database_port;dbname=$config->database_name", $config->database_user, $config->database_password, array(PDO::ATTR_PERSISTENT => false));
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("SELECT * FROM video_domain_list");
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_OBJ);
    $result = $stmt->fetchAll();
    $return = [
        'status' => 1,
        'data' => [
            'greenips' => [],
            'ips' => [],
        ]
    ];
    foreach ($result as $row) {
        if ($row->green)
            $return['data']['greenips'][] = $row->url;
        else
            $return['data']['ips'][] = $row->url;
    }
    $return = json_encode($return);
    $redis->set('domain:list', $return);
    $redis->close();
    $db = null;
    echo $return;
    exit;
} catch (\Exception $e) {
    $redis->close();
    $db = null;
    echo json_encode([
        'status' => 0,
        'msg' => $e->getTraceAsString(),
        'data' => [
            'greenips' => [],
            'ips' => [],
        ]
    ]);
}