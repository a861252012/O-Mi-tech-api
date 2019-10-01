<?php
/**
 * Created by PhpStorm.
 * Date: 15-4-24下午3:46
 * @Author   Orino
 * Descrition:
 */
define('BASEDIR',dirname(__DIR__));
include BASEDIR.'/pdomysql.php';

if( !defined('APP_DIR') ){
    exit('Not Allowed');
}
//echo md5("abc".time()); exit;
//$len = mt_rand(4,8);echo $len; exit;
//$a=array("red","green","blue","yellow");print_r(array_rand($a,2));exit;

$db = pdo();
$_redisInstance = new \Redis();
//$_redis_ip_port = explode(':',$_W['REDIS_CLI_IP_PORT']);
$_redisIsConnected = $_redisInstance->connect($_W['redis']['default']['host'],$_W['redis']['default']['port']);
$redis_password=$_W['redis']['default']['password'];
$_redisIsConnected=$_redisIsConnected && $_redisInstance->auth($redis_password);
if( $_redisIsConnected == false){
    exit('reids is  disconnect!');
}

function  findOneBy($assoc,$field='*',$master=true,$tablename='video_user'){
    if($master == false){
        $stmt = $GLOBALS['db']->prepare('select '.$field.' from `'.$tablename.'` where '.$assoc['column'].'=:'.$assoc['column']);//预编译，防止sql注入
    }else{
        $stmt = $GLOBALS['db']->prepare('/*'.MYSQLND_MS_MASTER_SWITCH.'*/select '.$field.' from `'.$tablename.'` where '.$assoc['column'].'=:'.$assoc['column']);//预编译，防止sql注入
    }
    $stmt->bindValue(':'.$assoc['column'], $assoc['value']);
    $stmt->execute();
    $stmt = $stmt->fetch(PDO::FETCH_ASSOC);
    return $stmt?$stmt:null;
}
function writeLog( $filename, $str){
    file_put_contents($filename,    $str.PHP_EOL,FILE_APPEND);
}


function pdoAdd($arr,$tablename){
    $new_arr = array();
    $cols1 = $cols2 = '';
    foreach($arr as $key=>$item){
        $new_arr[':'.$key] = $item;
        $cols1 .= $cols1 ==''?'`'.$key.'`':',`'.$key.'`';
        $cols2 .= $cols2 ==''?':'.$key.'':',:'.$key.'';
    }
    $sql = 'INSERT INTO `'.$tablename.'` ('.$cols1.') VALUES ('.$cols2.')';

    $stmt = $GLOBALS['db']->prepare($sql);
    $stmt->execute($new_arr);


    return $GLOBALS['db']->lastInsertId();
}


//分组配置，nums是产生对应的数量用户，points钱数，道具id，expire道具赠送天数
$created = $logined = date('Y-m-d H:i:s');
$end_time = date('Y-m-d H:i:s',time()+86400*40);
//线上配置 46
$groupArr = array(
    array('nums'=>10*10000, 'did'=>1, 'points'=>1000,'vip_end'=>$end_time,'vip'=>'1101'),
    //array('nums'=>500, 'did'=>46, 'points'=>1000,'vip_end'=>$end_time,'vip'=>'1101'),
);

//获取随机的昵称，要与用户表对比
function getNickNameRand($salt=false){
    static $nicknameArr = array(
        "A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z",
        "a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z",
        0,1,2,3,4,5,6,7,8,9,
        '夏','苏','浅','诗','漠','染','安','陌','木','伊','帆','凉','落','尘','语','轩','歌','熙','夕','影','然','枫','风','吕'
    );
    static $repeatArr = array(
        '颜','唯','洛','雨','悠','子','筱','简','晗','宇','景','小'
    );

    $randKeys = array_rand($nicknameArr,8);
    $str = '';
    $i = 0;
    if( $salt == true ){
        $j = mt_rand(0,7);
    }

    foreach( $randKeys as $item ){
        if( $salt  && $j == $i){
            $str .=  $repeatArr[array_rand($repeatArr,1)];
        }else{
            $str .= $nicknameArr[$item];
        }
        $i++;
    }
    return $str;
}
//获取随机的注册帐号，要与用户表对比
function getUserNameRand( $salt=false ){
    //这2个参数可配置可以使用注册邮箱重复率下降
    //性吧资源
    static $mailpreFix = 'dl_';//注册邮箱的前缀
    static $mailSuffix = '@agent.com';//注册邮箱的后缀
    static $randkeys  =  array(
        "a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z",
        0,1,2,3,4,5,6,7,8,9
    );
    static $randkeys2 = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
    $len = mt_rand(4,8);//长度随机
    $arrKeys = array_rand($randkeys,$len);
    $str = '';
    $i = 0;
    if( $salt == true ){
        $len = count($arrKeys);
        $j = mt_rand(0,$len-1);
    }
    foreach( $arrKeys as $item ){
        if( $salt  && $j == $i){
            $str .=  $randkeys2[array_rand($randkeys2,1)];
        }else{
            $str .= $randkeys[$item];
        }
        $i++;
    }
    return $mailpreFix.$str.$mailSuffix;
}

function getPassWordRand( $salt=false ){
    static $randkeys  =  array(
        "a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z",
        0,1,2,3,4,5,6,7,8,9,
        "A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"
    );
    static $randkeys2 = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
    $len = mt_rand(15,18);//长度随机
    $arrKeys = array_rand($randkeys,$len);
    $str = '';
    $i = 0;
    if( $salt == true ){
        $len = count($arrKeys);
        $j = mt_rand(0,$len-1);
    }
    foreach( $arrKeys as $item ){
        if( $salt  && $j == $i){
            $str .=  $randkeys2[array_rand($randkeys2,1)];
        }else{
            $str .= $randkeys[$item];
        }
        $i++;
    }
    return $str;
}


//1天秒数目
$oneday = 86400;
$password = md5('sex8vip888');//$mailpreFix,$mailSuffix,$groupArr可配置

function checkUsername(&$_redisInstance,$username){
    if( $_redisInstance->hExists('husername_to_id',$username)  || findOneBy(array('column'=>'username','value'=>$username),'username') != null ){
        $username = getUserNameRand(true);
        return checkUsername($_redisInstance ,$username);
    }
    return $username;
}

function checkNickName(&$_redisInstance,$nickname){
    if( findOneBy(array('column'=>'nickname','value'=>$nickname),'nickname') != null ){
        $nickname =  getNickNameRand(true);
        return checkNickName($_redisInstance, $nickname);
    }
    return $nickname;
}
function genRegUsers(array $arr,&$_redisInstance,&$oneday,&$created,&$logined,&$password){
    for( $i = 0;$i < $arr['nums'];$i++){
        $username = checkUsername( $_redisInstance, getUserNameRand());
        $nickname =  checkNickName( $_redisInstance, getNickNameRand() );
        $password =  getPassWordRand();
        //查看是否有赠送等级
        $vip = isset($arr['vip']) ? $arr['vip'] : 0;
        $vip_end = isset($arr['vip_end']) ? $arr['vip_end'] : 0;
        $did = isset($arr['did']) ? $arr['did'] : 0;
        $is_rich =   $is_points = $is_vip = false;
        $userdata = array(
            'did'=>  $did,
            //'roled'=> 3,        //todo
            'username'=> $username,
            'nickname'=> $nickname,
            'password'=> md5($password),
            'created'=> $created,
            'logined'=> $logined,
        );
        if( !empty($arr['rich']) && !empty($arr['lv_rich']) ){
            $userdata['rich']    = $arr['rich'];
            $userdata['lv_rich'] = $arr['lv_rich'];
            $is_rich = true;
        }

        //查看是否有赠送钻石
        if(!empty($arr['points'])){
            $userdata['points'] = $arr['points'];
            $is_points =true;
        }


        //开通贵族
        if(!empty($arr['vip']) && !empty($arr['vip_end'])){
            $userdata['vip'] = $arr['vip'];
            $userdata['vip_end'] = $arr['vip_end'];
            $is_vip = true;
        }

        //插入数据库
        $insertId = pdoAdd($userdata,'video_user');

        //增加开通记录--后台赠送
        if($is_vip && $insertId){
            $group = findOneBy(['column'=>'level_id','value'=>$vip],'*',true,'video_level_rich');
            $system = unserialize($group['system']);
            pdoAdd([
                'uid'=>$insertId,
                'gid'=>$group['gid'],
                'level_id'=>$vip,
                'type'=>3,
                'rid'=>0,
                'create_at'=>date('Y-m-d H:i:s'),
                'end_time'=>$arr['vip_end'],
                'status'=>1,
                'open_money'=>$system['open_money'],
                'keep_level'=>$system['keep_level']
            ],'video_user_buy_group');
        }


        $usr_info = findOneBy(array('column'=>'nickname','value'=>$nickname));
        //将查询出来的用户数据同步到redis
        $_redisInstance->hset('husername_to_id', $username, $insertId);
        $_redisInstance->hset('hnickname_to_id', $nickname, $insertId);

        $points = $arr['points'];
        $filename = APP_DIR . '/app/logs/reg-list-'.date('Ymd').'.log';
        $str = 'uid:'.$insertId.' username:'.$username.' password:'.$password.' did:'.$did.' points:'.$points.' vip:'.$vip.' vip_end:'.$vip_end;
        writeLog($filename,$str);
    }
}
$time1 = microtime(true);
echo 'Please waiting ... '.PHP_EOL;
foreach( $groupArr as $item ){
    genRegUsers($item,$_redisInstance,$oneday,$created,$logined,$password);
}
$_redisInstance->close();
echo 'elapsed time->'.intval(microtime(true)-$time1).'s'.PHP_EOL;
