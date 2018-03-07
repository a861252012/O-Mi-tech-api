<?php

define('PDO_DEBUG',false); // PDO调试
// 定义web目录位置
define('BASEDIR',dirname(__DIR__));

ini_set("display_errors","On");
error_reporting(E_ALL);
// 加载项目入口
$app = require BASEDIR . '/Vcore/App/app.php';

// 执行项目 Application.goRun()
$app->goRun();