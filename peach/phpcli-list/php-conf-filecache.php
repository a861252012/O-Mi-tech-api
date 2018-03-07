#!/usr/bin/php
<?php
/**
 *将symfony的缓存放入redis，并作为初始化v项目启动脚本,暂时不指出
 * Created by PhpStorm.
 * Date: 15-5-27上午10:25
 * @Author   Orino
 * Descrition: 
 */
include __DIR__.'/./yaml-parser.php';
//$Data = Spyc::YAMLLoad(__DIR__.'/../app/config/parameters.yml');
$Data = Spyc::YAMLLoad('/etc/video-front-conf/parameters.yml');
$_W = $Data['parameters'];
//$Data = Spyc::YAMLLoad(__DIR__.'/../app/config/payParameters.yml');
$Data = Spyc::YAMLLoad('/etc/video-front-conf/payParameters.yml');
$_W +=  $Data['parameters'];
unset($Data);
//$_W = preg_replace('/\s+'.PHP_EOL.'/','',var_export($_W,true));
//var_dump($_W);exit;
file_put_contents(__DIR__.'/../app/cache/cli-files/php-conf-cache.php',
    '<?php '.PHP_EOL.'return '.preg_replace('/[\s'.PHP_EOL.']+/m','',var_export($_W,true)).';'
);