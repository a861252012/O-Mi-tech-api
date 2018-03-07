<?php
if (!defined('BASEDIR')) {
    exit('File not found');
}
// 任务计划类
$cron->addRoute(['POST'], '/cron/{task}', ['uses' => 'App\Controller\CrontabController@index']);
