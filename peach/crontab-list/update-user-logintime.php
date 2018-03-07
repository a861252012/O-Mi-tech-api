#!/usr/bin/php
<?php
define('BASEDIR',dirname(__DIR__));
include BASEDIR.'/pdomysql.php';
if( !defined('APP_DIR') ){
    exit('Not Allowed');
}
$filename = date('YmdHi', strtotime('-1 minutes') );//前一分钟的文件名称
$path = APP_DIR.'/user-logtime/'.$filename;
if( !is_file($path) || !is_readable($path) ){
    exit('目标文件不存在或者不可写');
}

$data = explode(PHP_EOL,file_get_contents($path));
if( !$data ){
    exit('空文件');
}

$len = count($data)-1;//去除最后的空字符串
$confArr = array();
for($i=0;$i<$len;$i++){
    $item = explode('|',$data[$i]);
    $confArr[$item[0]] = $item[1];
}
$db = pdo(true);
//$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
$sql=$sql1='';
foreach( $confArr as $key=>$item ){
    PDO_DEBUG &&  $sql1 .= "UPDATE `video_user` SET `logined` = '{$item}' where `uid`=".intval($key).";".PHP_EOL;
    $sql .= "UPDATE `video_user` SET `logined` = '{$item}' where `uid`=".intval($key).";";
}

if(PDO_DEBUG){
    echo PHP_EOL.$sql1.PHP_EOL;
}
$db->exec($sql);
$errInfo = pdo()->errorInfo();
if( intval($errInfo[0]) == 0 && is_writable($path) ){
    unlink($path);//不怎么管用
}
