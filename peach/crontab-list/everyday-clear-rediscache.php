#!/usr/bin/php
<?php
/**
 * Created by PhpStorm.
 * Date: 15-5-27下午2:13
 * @Author   Orino
 * Descrition: 
 */
define('BASEDIR',dirname(__DIR__));
include BASEDIR.'/pdomysql.php';
$_redisInstance = new \Redis();
$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);
$_redisIsConnected = $_redisInstance->connect($_redis_ip_port[0],$_redis_ip_port[1]);
$redis_password=$_W['redis']['default']['password'];
$_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
if( $_redisIsConnected == false){
    exit('reids is  disconnect!');
}
//每天发私信计数
$key1 = (array)$_redisInstance->keys('hvideo_mail*');

//当天验证密码计数，大于5就要出现验证码
$key2 = (array)$_redisInstance->keys('keys_room_passwd*');//数组合并
//每天投诉建议计数
$key3 = (array)$_redisInstance->keys('key_complaints_flag*');
//每天限制ip注册计数
$key4 = (array)$_redisInstance->keys('hreg_ip_limit*');
$keys = array_merge($key1,$key2,$key3,$key4);

foreach( $keys as $item ){
    $_redisInstance->del($item);
}

// TODO　贵族体系的到期通知
// 即将到期的通知
$db = pdo();
$date = time() + 7*24*60*60; //提前7天通知 每天一条
$sql = 'select * from video_user where vip!=0 and vip_end>"'.date('Y-m-d H:i:s') .'" and vip_end < "'.date('Y-m-d H;i:s',$date).'"';
$data = pdo_fetchall($sql);
if($data){
    $msg = array(
        'rec_uid'=>'',
        'content'=>'贵族保级即将失败提醒：您的贵族即将到期！请尽快充值保级！',
        'category'=>1,
        'created'=>date('Y-m-d H:i:s')
    );
    foreach($data as $value){
        $level_name = $_redisInstance->hGet('hgroups:special'.$value['vip'],'level_name');
        $msg['rec_uid'] = $value['uid'];
        $msg['content'] = '贵族保级即将失败提醒：您的'.$level_name.'贵族到期日：'.$value['vip_end'].'！请尽快充值保级！';
        // 发送消息
        pdo_insert('video_mail', $msg);
    }
}

// 已经到期的下掉贵族
$sql = 'select uid,vip,vip_end from video_user where vip!=0 and vip_end<"'.date('Y-m-d H:i:s').'"';
$data= pdo_fetchall($sql);
if(!$data){
    return;
}
foreach($data as $user){
    pdo_update('video_user',array('vip'=>0,'vip_end'=>'','hidden'=>0), array('uid'=>$user['uid']));
    $_redisInstance->hSet('huser_info:'.$user['uid'],'vip','0');
    $_redisInstance->hSet('huser_info:'.$user['uid'],'hidden','0');
    $_redisInstance->hSet('huser_info:'.$user['uid'],'vip_end','');
    $delMount = 'delete from video_pack where uid=:uid and gid >=120101 and gid <=120107';
    pdo_query($delMount,array('uid'=>$user['uid']));
    $_redisInstance->del('user_car:'.$user['uid']);
}

