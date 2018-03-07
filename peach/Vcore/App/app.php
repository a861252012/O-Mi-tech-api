<?php
if(!defined('BASEDIR')){
    exit('File not found');
}
// 加载自动加载类中 composer方式 并注册到系统中
$loader = include BASEDIR.'/Vcore/vendor/autoload.php';
spl_autoload_register(array($loader,'loadClass'));

// 初始化框架入口 容器
$app = new \Core\Application();

// 注册服务到框架中，全局都可以调用 调用时候 $container->make(service)
$app->alias('App\Service\Task\TaskService','taskServer');
$app->alias('App\Service\Message\MessageService','messageServer');
$app->alias('App\Service\User\UserService','userServer');
$app->alias('App\Service\UserGroup\UserGroupService','userGroupServer');
$app->alias('App\Service\System\SystemService', 'systemServer');
$app->alias('App\Service\Lottery\LotteryService', 'lotteryServer');
$app->alias('App\Service\Room\RoomService', 'roomService');
$app->alias('App\Service\Room\GameService', 'gameService');
$app->alias('App\Service\Room\SocketService', 'socketService');
$app->alias('App\Service\Safe\SafeService', 'safeService');
$app->alias('App\Service\Safe\RtmpService', 'rtmpService');
$app->alias('App\Service\Auth\JWTAuthService', 'JWTAuth');

// 加载项目所有路由配置
include 'Config/route.php';
include BASEDIR.'/Vcore/Core/function.php';

return $app;
