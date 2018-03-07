#! /usr/bin/php
<?php

define('PDO_DEBUG',false); // PDO调试
// 定义web目录位置
define('BASEDIR',dirname(__DIR__));
// 加载项目入口
$cron = require BASEDIR . '/Vcore/App/cron.php';
// 执行项目 Application.goRun()
$cron->goCron($argv);
