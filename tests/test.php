<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2018/5/30
 * Time: 11:23
 */

//$section = file_get_contents('http://10.1.100.157:81/api/storage/s1/videolistall.json', NULL, NULL, -10);
$rs = "";
for ($i=0; $i<3600; $i++){

//    $file = 'http://10.1.100.157:81/api/storage/s1/videolistall.json';
//    $section = "";
//    $fp = fopen($file , 'r');
//    if(flock($fp , LOCK_EX)){
//        $section = fread($fp , filesize($file));
//        flock($fp , LOCK_UN);
//    } else{
//        $section = "Lock file failed...\n";
//    }
//    fclose($fp);

    list($u,$start) = explode(' ',microtime());
    $us = $start+$u;

    $section = file_get_contents('http://10.1.100.157:81/api/storage/s1/videolistall.json');
    $rs = substr($section,-10).PHP_EOL;


    list($eu,$end) = explode(' ',microtime());
    $eus = $end+$eu;
    echo '读文件all 开始：'.date('Y-m-d H:i:s',$start).'-'.$u.' 结束：'.date('Y-m-d H:i:s',$end).'-'.$eu.' 花费时间:'.($eus-$us) .'   '.$rs. PHP_EOL;

   // file_put_contents('./data',$rs,FILE_APPEND);
    sleep(1);
}

//var_dump($section);