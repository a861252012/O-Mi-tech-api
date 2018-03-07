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
function writeLog( $uid, $username,$nums,$points,$gid, $expire){
    file_put_contents(APP_DIR . '/app/logs/reg-list-'.date('Ymd').'-'.$nums.'-'.$points.'-'.$gid.'-'.$expire.'.log',$uid.': '.$username.PHP_EOL,FILE_APPEND);
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
$groupArr = array(
    array('nums'=>200, 'did'=>17, 'points'=>0,/*'gid'=> 120009,'expire'=> 30,*/'rich'=>'','lv_rich'=>''),
    // array('nums'=>2000,'points'=>20,'gid'=> 120009,'expire'=> 30),
    // array('nums'=>110,'points'=>10,'gid'=> 120009,'expire'=> 30),
);

//获取随机的昵称，要与用户表对比
function getNickNameRand($salt=false){
    static $nicknameArr = array(
        "A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z",
        "a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z",
        0,1,2,3,4,5,6,7,8,9,
        '夏','苏','浅','诗','漠','染','安','陌','木','伊','帆','凉','落','尘','语','轩','歌','熙','夕','影','然','枫','风','吕'
    );
    //   '临','兵','斗','者','皆','阵','列','在','前'
    static $repeatArr = array(
        '颜','唯','洛','雨','悠','子','筱','简','晗','宇','景','小'
    );

//     '乾','坤','震','巽','坎','离','艮','兑',
//     '天','地','雷','风','水','火','山','泽'
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
    static $mailpreFix = 's8_';//注册邮箱的前缀
    static $mailSuffix = '@sex8.cc';//注册邮箱的后缀
    //博马资源
    // static $mailpreFix = 'bo';//注册邮箱的前缀
    // static $mailSuffix = '@bmw.com';//注册邮箱的后缀
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


$created = $logined = date('Y-m-d H:i:s');
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
        //查看是否有赠送等级
        $is_rich =   $is_points = false;
        $userdata = array(
            'did'=>  isset($arr['did']) ? $arr['did'] : 0,
            'username'=> $username,
            'nickname'=> $nickname,
            'password'=> $password,
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

        //插入数据库
        $insertId = pdoAdd($userdata,'video_user');
        //将送钱的记录放入到记录表
        if($is_points){
           pdoAdd(array(
                'uid'=>$insertId,
                'points'=>$arr['points'],
                'created'=>date('Y-m-d H:i:s',time()),
                'pay_type'=> 5,
                'pay_status'=>1,
                'nickname'=>$nickname,
            ),'video_recharge');

        }

        //如有赠送等级则插入送经验记录
        if($is_rich){
            pdoAdd(array(
                'uid'=>$insertId,
                'exp'=>$arr['rich'],
                'status'=>2,
                'type'=>1,
                'roled'=>0,
                'curr_exp'=>0
            ),'video_user_mexp');
        }


        if( !empty($arr['expire'])  &&  !empty($arr['gid']) ){
            $expire = $arr['expire']*$oneday;
            //将赠送的道具，绑定给指定用户
            pdoAdd(array(
                'uid'=>$insertId,
                'gid'=> $arr['gid'],
                'num'=> 1,
                'expires'=> time()+$expire
            ),'video_pack');
        }

        $usr_info = findOneBy(array('column'=>'nickname','value'=>$nickname));
        //将查询出来的用户数据同步到redis
        $_redisInstance->hmset('huser_info:'.$insertId, $usr_info);

        $_redisInstance->hset('husername_to_id', $username, $insertId);
        $_redisInstance->hset('hnickname_to_id', $nickname, $insertId);

        //赠送新用户3次抽奖机会
        // $_redisInstance->hset('hlottery_ary',$insertId,3);
        writeLog($insertId,$username ,$arr['nums'], $arr['points'] ,$arr['gid'],$arr['expire']);
    }
}
$time1 = microtime(true);
echo 'Please waiting ... '.PHP_EOL;
foreach( $groupArr as $item ){
    genRegUsers($item,$_redisInstance,$oneday,$created,$logined,$password);
}
$_redisInstance->close();
echo 'elapsed time->'.intval(microtime(true)-$time1).'s'.PHP_EOL;
