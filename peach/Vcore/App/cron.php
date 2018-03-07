<?php
if(!defined('BASEDIR')){
    exit('File not found');
}
// 加载自动加载类中 composer方式 并注册到系统中
$loader = include BASEDIR.'/Vcore/vendor/autoload.php';
spl_autoload_register(array($loader,'loadClass'));

// 初始化框架入口 容器
$cron = new \Core\Application();

// 注册服务到框架中，全局都可以调用 调用时候 $container->make(service)
$cron->alias('App\Service\Task\TaskService','taskServer');
$cron->alias('App\Service\Message\MessageService','messageServer');
$cron->alias('App\Service\User\UserService','userServer');
$cron->alias('App\Service\UserGroup\UserGroupService','userGroupServer');
$cron->alias('App\Service\System\SystemService', 'systemServer');
$cron->alias('App\Service\Lottery\LotteryService', 'lotteryServer');
$cron->alias('App\Service\Room\RoomService', 'roomServer');
$cron->alias('App\Service\Room\GameService', 'gameServer');
$cron->alias('App\Service\Room\SocketService', 'socketServer');
// 加载项目所有路由配置
include 'Config/cronroute.php';

return $cron;
