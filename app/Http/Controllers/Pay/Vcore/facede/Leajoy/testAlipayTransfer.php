<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/24
 * Time: 9:15
 */

define('BASEDIR','/www/peach-front');
require_once BASEDIR.'/Vcore/vendor/autoload.php';
require_once '../../../vendor/autoload.php';
$config = require_once '../../config/config.php';

$a = new \Pay\facede\Leajoy\AlipayTransfer($config['three_plat']['2']);


use Illuminate\Database\Capsule\Manager as Capsule;//如果你不喜欢这个名称，as DB;就好

$capsule = new Capsule;

$front=[
    'host'=>'10.1.100.192:3366',
    'user'=>'clark',

    'pwd'=>'123456',
    'db'=>'peach',
];
$database['front'] = [
    'driver'=>'mysql',
    'host'=>$front['host'],
    'database'=>$front['db'],
    'username'=>$front['user'],
    'password'=>$front['pwd'],
    'charset'=>'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
];
// 创建链接
$capsule->addConnection($database['front']);

// 设置全局静态可访问
$capsule->setAsGlobal();

// 启动Eloquent
$capsule->bootEloquent();

function testPay($a){
    $data['pid']=time();
    $data['money']='10';
    $data['remark']='王五';
    $data['order_id']=time()*2;
    $data['channel']='1';
    $data['uid']=1000;
    $data['username']='lili';

    $a->pay($data);

    var_dump($a->view_para);
}
function testTwig($a){
    $a->view_para=['hello'=>'im'];
    echo $a->build();
}

$a = new \Pay\facede\Leajoy\AlipayTransfer($config['three_plat']['2']);
testPay($a);