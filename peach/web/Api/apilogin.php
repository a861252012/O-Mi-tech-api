<?php
/**
 * Created by PhpStorm.
 * Date: 14-12-30上午9:51
 * @Author   Orino
 * Descrition:
 */
include dirname(__FILE__).'/apicommon.php';
if( !getenv('STRESS_ENV') &&  !defined('APP_DIR')  ){
    exit(json_encode(array(
       "status"=> 0,
        "msg" => "非法提交数据！"
    )));
}
define('NOW_TIME',      $_SERVER['REQUEST_TIME']);//定义请求时间
header('Content-Type: application/json');

/**
 * Class LoginRoute
 * @Author Orino
 *
 */
class LoginRoute
{
    const CLIENT_ENCRY_FIELD = 'v_remember_encrypt';
    const SEVER_SESS_ID = 'webonline';//在线用户id
  //  const  TOKEN_CONST = 'auth_key';
    const WEB_UID = 'webuid';
    private $_online  = false;
    private $_redisInstance = null;
    private $_redisIsConnected = false;
    private $_sess_id = null;
    private $_confAssoc = array();
    private $_dbInstance = null;
    private $_isGetCache = false;
    private $_updateTime = false;
    private $_points = 0;
    private $_redis_ip_port = null;
    private $_common_domain = null;//将cookie限制在.1room.cc类是域名下
    private $_encrypt_key = null;//将cookie限制在.1room.cc类是域名下
    public function __construct($conf)
    {

        //伪造symfony的session key一样
        if( !isset( $_SESSION['_sf2_attributes']) ){
            $_SESSION['_sf2_attributes'] = array();
        }
        $this->_confAssoc = $conf;
        if( !isset($this->_confAssoc['REDIS_CLI_IP_PORT']) ){
            exit('redis配置文件有问题');
        }
        $this->_redisInstance =  new \Redis();
        $redis_ip_port = $this->_confAssoc['REDIS_CLI_IP_PORT'];
        $this->_redis_ip_port = explode(':',$redis_ip_port);
        $this->_redisIsConnected = $this->_redisInstance->connect($this->_redis_ip_port[0],$this->_redis_ip_port[1]);
        $redis_password=$this->_confAssoc['redis']['default']['password'];
        $this->_redisIsConnected=$this->_redisIsConnected && $this->_redisInstance->auth($redis_password);
        if(  $this->_redisIsConnected == false ){
            exit(json_encode(array(
                "status"=> 0,
                "msg" => "网络繁忙！"
            )));
        }
        $this->_common_domain = $conf['v_remember_encrypt'];
        $this->_encrypt_key = $conf['WEB_SECRET_KEY'];
    }

    private function _close(){
        if( $this->_redisIsConnected ){
            $this->_redisInstance->close();
        }
        if( $this->_dbInstance != null ){
            $this->_dbInstance = null;
        }
    }

    /**
     * 释放redis和mysql的连接
     */
    public function __destruct()
    {
       $this->_close();
    }

    /**
     * @return null
     * @Author Orino
     */
    private function  pdoInstance()
    {
        if(  $this->_dbInstance != null )return null;
        $this->_dbInstance = new PDO(
            'mysql:host='.$this->_confAssoc['database_host'].';port='.$this->_confAssoc['database_port'].';dbname='.$this->_confAssoc['database_name'],
            $this->_confAssoc['database_user'],
            $this->_confAssoc['database_password'],
            array( PDO::ATTR_PERSISTENT => false,PDO::ATTR_TIMEOUT => 3 )//非永久连接
        );
        $this->_dbInstance->exec('set names utf8');
        if( $this->_dbInstance == null ){
            exit(json_decode(array(
                "status"=> 0,
                "msg" => "数据库异常！"
            )));
        }
    }
    private function checkLogin($encryptStr='')
    {
        if( isset($_SESSION) && isset($_SESSION['_sf2_attributes'][self::SEVER_SESS_ID])){
            $this->_online = $_SESSION['_sf2_attributes'][self::SEVER_SESS_ID];
        }elseif(  isset( $_COOKIE[self::CLIENT_ENCRY_FIELD] ) &&  $_COOKIE[self::CLIENT_ENCRY_FIELD] !=null ){
            //记住密码的功能
           $this->encryptHandler($_COOKIE[self::CLIENT_ENCRY_FIELD]);
        }elseif( $encryptStr != ''){
            $this->encryptHandler($encryptStr);
        }elseif( $this->_online == false){
            $this->_clearCookie();
        }
        return $this->_online;
    }

    /**
     * 加密的字符串验证，验证成功后写入redis信息
     * @param $cookiestr
     * @return bool
     * @Author Orino
     */
    private function encryptHandler($cookiestr){
        $cookiestr  = explode('|', $cookiestr);
        if( count($cookiestr) != 2 ){
            return $this->_online;
        }
        $uid = intval($cookiestr[0]);
        //检查redis
        $userinfo = $this->_redisInstance->hGetAll('huser_info:'.$uid);
        if( ! $userinfo ){
            //检查数据库
            $userinfo = $this->findOneBy( array('column'=>'uid','value'=> $uid) );
        }
        //通过uid获取用户信息，检查并且验证密钥的合法性 用户登录邮箱和密钥md5
        if( !! $userinfo  && $this->remypwdMd5( $userinfo['username']) === $cookiestr[1] ){
            $huser_sid = $this->_redisInstance->hget('huser_sid',$userinfo['uid']);
            $this->_online = $userinfo['uid'];
            $this->writeRedis($userinfo,$huser_sid);
        }
    }

    /**
     * @param $username
     * @return string
     * @Author Orino
     */
    private function remypwdMd5($username){
        return md5($username.$this->_encrypt_key);
    }

    private function _clearCookie()
    {
        if( isset($_COOKIE[self::CLIENT_ENCRY_FIELD]) ){
            setcookie(self::CLIENT_ENCRY_FIELD, null, time()-31536000,'/',$this->_common_domain);
        }
        if( isset($_COOKIE[self::WEB_UID]) ){
            setcookie(self::WEB_UID, null, time()-31536000,'/',$this->_common_domain);
        }
    }


    private function _sendMsgBysystem($rec_uid,$content){
        $uid = $this->pdoAdd(array(
            'send_uid'=> 0,
            'rec_uid'=> $rec_uid,
            'content'=>$content,
            'category'=> 1,
            'status' => 0,
            'created'=> date('Y-m-d H:i:s')
        ),'video_mail');
    }
    private function pdoAdd($arr,$tablename){
        $new_arr = array();
        $cols1 = $cols2 = '';
        foreach($arr as $key=>$item){
            $new_arr[':'.$key] = $item;
            $cols1 .= $cols1 ==''?'`'.$key.'`':',`'.$key.'`';
            $cols2 .= $cols2 ==''?':'.$key.'':',:'.$key.'';
        }
        $this->pdoInstance();
        $sql = 'INSERT INTO `'.$tablename.'` ('.$cols1.') VALUES ('.$cols2.')';
        $stmt = $this->_dbInstance->prepare($sql);
        $stmt->execute($new_arr);
        return $this->_dbInstance->lastInsertId();
    }
    private function pdoExceByone($sql,$colname,$value){
        $sql .= ' where '.$colname.'=:'.$colname;
        $stmt = $this->_dbInstance->prepare($sql);//预编译，防止sql注入
        $stmt->bindValue(':'.$colname,$value);
        $stmt->execute();
        $stmt = $stmt->fetch(PDO::FETCH_ASSOC);
        return $stmt;
    }
    private  function curl_http($url, $data='', $method='GET'){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        if($method=='POST'){
            curl_setopt($ch, CURLOPT_POST, 1); // 发送一个常规的Post请求
            if ($data != ''){
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
            }
        }
        curl_setopt($ch,CURLOPT_TIMEOUT_MS,500);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

        $rs = curl_exec ($ch);//已经传送过来是json字符串
        $err = curl_error($ch);
        curl_close ($ch);
        if($err){
            return array(
                'ret'=>0,
                'msg'=>$err
            );
        }else{
            return array(
                'ret'=>1,
                'msg'=>json_decode($rs,true)
            );
        }
    }

    /**
     * 检查代理商注册，获取cookie的agent值，来关联代理商
     * @param $uid
     * @return bool|int
     */
    private function checkAgent($uid){
        if(!isset($_COOKIE['agent'])){
            return false;
        }
        $agenturl=$_COOKIE['agent'];
        $did = $this->findOneBy(array('column'=>'url','value'=>$agenturl),'id','false','video_domain');
        $agentid = $this->findOneBy(array('column'=>'did','value'=>$did['id']),'id','false','video_agents');
        if(empty($agentid)){
            return false;
        }
        $agent = array(
            'aid'=>$agentid['id'],
            'uid'=>$uid
        );
        $this->doclick($did);
        $this->pdoAdd($agent,'video_agent_relationship');
        return 0;
    }
    private  function doclick($did){//注册成功增加点击量
        $this->pdoInstance();
        $sql = 'update video_domain set click = click +1 where id = '.$did;
        $stmt = $this->_dbInstance->prepare($sql);
        $stmt->execute();
    }
    private function sendGift($uid, $addPoints){
        $logintime = date('Y-m-d H:i:s');
        /*  if( $login ){
              $rs = $this->curl_http($this->_confAssoc['REMOTE_JS_URL'].'/video_gs/web_api/add_point?uid='.$uid.'&points='.$addPoints.'&logined='.rawurlencode($logintime));
              if($rs['ret'] == 1 && $rs['msg']['ret'] == 1 ){
                  $this->_updateTime = $logintime;
              }
          }else{
              $rs = $this->curl_http($this->_confAssoc['REMOTE_JS_URL'].'/video_gs/web_api/add_point?uid='.$uid.'&points='.$addPoints);
          }*/

        $sql = 'UPDATE `video_user` SET `points`=`points`+?, logined=? WHERE uid=?';
        $stmt = $this->_dbInstance->prepare($sql);
        $stmt->execute(array($addPoints,$logintime,$uid));
        //$stmt = $this->pdoExceByone('select points from `video_user`','uid',$uid);
        //if( !$stmt) return false;
        $this->_updateTime = $logintime;
        $this->_redisInstance->hMset('huser_info:'.$uid,array('points'=>$this->_points+$addPoints,'logined'=>$logintime));
        return true;
    }

    private function commonsendGift($uid,$check, $pointNum){
        $expireDate = $this->_confAssoc['SEND_GIFT_EXPIRE'];
        if( !$expireDate || !$uid ){
            return false;
        }
        $expireDate = explode('/',$expireDate);
        $curDate  = date('Y-m-d H:i:s') ;
        if( $curDate < $expireDate[0] ||  $curDate > $expireDate[1] ){//判断送礼活动是否结束
            return false;
        }
        $hkey = 'huser_login_day';
        $serDay = date('Y-m-d');//服务器当天的日期
        if(  $check ){
            $curDay =  $this->_redisInstance->hget($hkey,$uid);
           // file_put_contents(APP_DIR.'user-logtime/login-per.log','uid'.$uid.';'.time().'|'.$curDay.'|'. $serDay.PHP_EOL);
            if( $curDay != $serDay ){//说明当天还没登录过
                $this->pdoInstance();
                $this->sendGift($uid,$pointNum);//$_updateTime
                $this->_sendMsgBysystem($uid,'今日签到成功，获得100个钻石奖励，请继续努力哦。');//登录送
                $this->_redisInstance->hset($hkey,$uid,$serDay);
            }
        }else{
            $this->pdoInstance();
            $this->sendGift($uid,$pointNum);
            $this->_sendMsgBysystem($uid,'恭喜您成为蜜桃儿会员，获得500个钻石的注册奖励。');//注册送
            $this->_redisInstance->hset($hkey,$uid, $serDay);
        }
    }
    public function handler()
    {
        $uname =isset($_REQUEST['uname']) ?$_REQUEST['uname']: null;
        $upass =isset($_REQUEST['password']) ?trim($_REQUEST['password']): null;
        if(  !isset($_REQUEST['_m']) ){
            $upass = decode($upass);
        }
        $retval =  $this->solveUserLogin($uname,$upass);
        if( $retval['status'] == 1){
            $this->commonsendGift($this->_online,true,$this->_confAssoc['LOGIN_SEND_POINT']);
            if( $this->_updateTime == false){
                //更新时间
                $this->_updateTime = date('Y-m-d H:i:s');
                $this->_redisInstance->hset('huser_info:'.$this->_online,'logined',  $this->_updateTime);
                file_put_contents(
                    APP_DIR.'user-logtime/'.substr( str_replace( array('-',':', ' '), array('','',''),$this->_updateTime ),0,12),//1分钟
                    $this->_online.'|'.$this->_updateTime.PHP_EOL,
                    FILE_APPEND
                );
                /*
                 $this->pdoInstance();
                $sql = 'UPDATE `video_user` SET logined=? WHERE uid=?';
                   $stmt = $this->_dbInstance->prepare($sql);
                   $stmt->execute(array( $this->_updateTime,$retval['uid']));*/
            }
        }

       if( getenv('STRESS_ENV') === 'true' && isset($_REQUEST['_m']) && $_REQUEST['_m'] == 'test'){
            $sessId = md5(uniqid(mt_rand(), true)).time();
            $strId = strval( $this->_online);
            $this->_redisInstance->set('PHPREDIS_SESSION:'.$sessId,'_sf2_attributes|a:1:{s:9:"webonline";s:'.strlen($strId).':"'.$strId.'";}_sf2_flashes|a:0:{}');
            $retval['msg'] = $sessId;
        }
        return json_encode($retval,JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param $assoc 条件column=>value
     * @param string $field 查询的字段
     * @param bool $master
     * @param string $tablename
     * @return null
     * @Author Orino
     */
    private  function findOneBy($assoc,$field='*',$master=true,$tablename='video_user'){
        $this->pdoInstance();
		if($master == false){
			$stmt = $this->_dbInstance->prepare('select '.$field.' from `'.$tablename.'` where '.$assoc['column'].'=:'.$assoc['column']);//预编译，防止sql注入
		}else{
			$stmt = $this->_dbInstance->prepare('/*'.MYSQLND_MS_MASTER_SWITCH.'*/select '.$field.' from `'.$tablename.'` where '.$assoc['column'].'=:'.$assoc['column']);//预编译，防止sql注入
		}
        $stmt->bindValue(':'.$assoc['column'], $assoc['value']);
        $stmt->execute();
        $stmt = $stmt->fetch(PDO::FETCH_ASSOC);
        return $stmt?$stmt:null;
    }
    private function solveUserLogin($username,$password){
        if( empty($username) || empty($password) ){
            return  array(
                'status'=>0,
                'msg'   =>'用户名或密码不能为空'
            );
        }

        $sCode = isset($_REQUEST['sCode'])?$_REQUEST['sCode']:null;



        //取uid
        /*  $uid =  $this->_redisInstance->hget('husername_to_id',$username);
         $pipeline = $this->_redisInstance->multi(Redis::PIPELINE);
         $pipeline->hget('huser_sid',$uid);
         $pipeline->hgetall('huser_info:'.$uid);
         $replies = $pipeline->exec();
         $huser_sid = $replies[0];
         $userinfo = $replies[1];

        if (!$userinfo) {
             if( !$uid ){
                 $userinfo = $this->findOneBy(array('column'=>'username','value'=>$username),'*',false);
             }else{
                 $userinfo =  $this->findOneBy(array('column'=>'uid','value'=>$uid),'*',false);
             }
            // $userinfo = $this->findOneBy(array('column'=>'username','value'=>$username),'*',false);
         } else{
                 $this->_isGetCache = true;
         }*/
        $uid =  $this->_redisInstance->hget('husername_to_id',$username);
        if (!$uid) {
            $userinfo = $this->findOneBy(array('column'=>'username','value'=>$username),'*',false);
        } else{
            $userinfo = $this->_redisInstance->hgetall('huser_info:'.$uid);

            if( !$userinfo ){
                $userinfo = $this->findOneBy(array('column'=>'uid','value'=>$uid),'*',false);
            }else{
                $this->_isGetCache = true;
            }
        }

        //如果获取不到用户信息，返回提示信息
        $times = 0;
        $isCode = 0;
        if ( !$userinfo ) {
            return array(
                'status'=>0,
                'msg'=>'帐号密码错误!'
            );
        }
        $passFlag = true;
        $times = intval($this->_redisInstance->hget('hlogin_authcode',$userinfo['uid'])) ?: 0;

        if( $times >= 5 && !checkCaptcha($sCode,!$this->_confAssoc['skip_captcha_login']) ){
            return array(
                "status"=> 0,
                "msg" => "验证码错误，请重新输入！",
                "failNums"=>$times
            );
        }
        if(  $userinfo['password'] != md5($password) ){
            //计数
            $this->_redisInstance->hset('hlogin_authcode',$userinfo['uid'],++$times);
            return array(
                'status'=>0,
                'msg'=>'帐号密码错误!',
                'failNums'=>$times
            );
        }

        //后台设置了是否登录
        if ($userinfo['status'] != 1) {
            return array(
                'status'=>0,
                'msg'=>'您的账号已经被禁止登录，请联系客服！',
            );
        }
        $huser_sid = $this->_redisInstance->hget('huser_sid',$uid);
        $this->writeRedis($userinfo,$huser_sid);
        $remember = isset($_REQUEST['v_remember'])?intval($_REQUEST['v_remember']):0;
        if( $remember == 1){
         //   $expireDay = 604800;//7*24*60*60
            //记住我的功能，将uid,|,用户名，密钥 一起md5加密，验证的时候可以用|分割
            setcookie(
                self::CLIENT_ENCRY_FIELD, $userinfo['uid'].'|'.$this->remypwdMd5($userinfo['username']),
                time()+604800 ,'/',
                $this->_common_domain
            );
            $day = date('Ymd');
            $times =  intval($this->_redisInstance->hget('hlogin_remember',$day));
            $this->_redisInstance->hset('hlogin_remember',$day,++$times);
        }
        //清除计数
        $this->_redisInstance->hset('hlogin_authcode',$userinfo['uid'],0);
        return array(
            'status'=>1,
            'msg'=>$this->_sess_id,
            //   'uid'=>$userinfo['uid']
        );
    }
    /**
     * 写入redis
     **/
    private function writeRedis($userInfo,$huser_sid){
        try{
            //判断当前用户session是否是最新登陆
            $this->_online  =  $userInfo['uid'];
            //设置新session
            $_SESSION['_sf2_attributes'][self::SEVER_SESS_ID] = $userInfo['uid'];
            setcookie(self::WEB_UID,$userInfo['uid'],0,'/',$this->_common_domain);//用于首页判断
            $this->_sess_id = session_id();

            if(empty($huser_sid)){ //说明以前没登陆过，没必要检查重复登录
                $this->_redisInstance->hset('huser_sid',$userInfo['uid'],$this->_sess_id);
            }elseif($huser_sid !=   $this->_sess_id){//有可能重复登录了
                //更新用户对应的sessid
                $this->_redisInstance->hset('huser_sid',$userInfo['uid'], $this->_sess_id );
                //删除旧session，踢出用户在上一个浏览器的登录状态
                $this->_redisInstance->del('PHPREDIS_SESSION:'.$huser_sid);
            }
            if( $this->_isGetCache == false){
                $this->_redisInstance->hset('husername_to_id',$userInfo['username'],$userInfo['uid']);
                $this->_redisInstance->hset('hnickname_to_id',$userInfo['nickname'],$userInfo['uid']);
                $this->_redisInstance->hmset('huser_info:'.$userInfo['uid'],$userInfo);
            }
            $this->_points = $userInfo['points'];
        }catch(Exception $e){
            unset( $_SESSION['_sf2_attributes'][self::SEVER_SESS_ID]);
            setcookie(self::WEB_UID, null, time()-31536000,'/',$this->_common_domain);
            $this->_online  = false;
        }
    }

    public function registerAct(){
        if(  $this->checkLogin() ){
            return
                json_encode(array(
                    "status"=> 0,
                    "msg" => "已经在登录状态中！"
                ));
        }
        $sCode = isset($_REQUEST['sCode'])?$_REQUEST['sCode']:null;
        //start 开始验证ip限制
        $isRegFlag = false;//不能注册
        $curIp = $this->getClientIp();
        $invitationKey = 'hreg_ip_limit';
        $info = $this->_redisInstance->hget($invitationKey,$curIp);
        $curDay = date('Y-m-d');
        if ( $info != null ){
            $info = json_decode($info,true);//存在就解析该值
        }
        if( $info == null ||  $info['curDate'] != $curDay ||  $info['count'] < 10 ){
            $isRegFlag = true;
        }
        if( ! $isRegFlag ){
            return json_encode(array(
                "status"=> 0,
                "msg" => "此IP地址本日注册帐号达到上限，请明天再试！"
            ));
        }
        //end ip限制验证完毕


        if(  !checkCaptcha(!$sCode,$this->_confAssoc['SKIP_CAPTCHA_REG']) ){
            return json_encode(array(
                "status"=> 0,
                "msg" => "验证码错误！请重新刷新验证码"
            ));
        }
        $password = isset($_REQUEST['password'])?$_REQUEST['password']:null;
        $repassword = isset($_REQUEST['repassword'])?$_REQUEST['repassword']:null;

        $password = trim(decode($password));
        $repassword = trim(decode($repassword));

        if( $password != $repassword ){
            return json_encode(array(
                "status"=> 0,
                "msg" => "两次输入的密码不相同！"
            ));
        }
        //表单验证
        $username = isset($_REQUEST['username'])?trim($_REQUEST['username']):null;
        if(  !preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/',$username) || strlen($username) < 5 || strlen($username) > 30  ){
            return  json_encode(array(
                "status"=> 0,
                "msg" => "注册邮箱不符合格式！(5-30位的邮箱)"
            ));
        }
        if( $this->_redisInstance->hExists('husername_to_id',$username)  || !!$this->findOneBy(array('column'=>'username','value'=>$username),'username') ){
            return  json_encode(array(
                "status"=> 0,
                "msg" => "注册邮箱重复，请换一个试试！"
            ));
        }
        $nickname = isset($_REQUEST['nickname'])?trim($_REQUEST['nickname']):null;
        //if( strlen($nickname) < 2 || strlen($nickname) > 16 || !preg_match("/^[A-Za-z0-9_".chr(0xa1)."-".chr(0xff)."]+[^_]$/",$nickname)|| !!$this->_findByUserName(array('nickname'=>$nickname)) ){
        $len = $this->count_chinese_utf8($nickname);
        //昵称不能使用/:;\空格,换行等符号。
        if( $len < 2 || $len > 8 || !preg_match("/^[^\s\/\:;]+$/",$nickname)  ){
            return json_encode(array(
                "status"=> 0,
                "msg" => "注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)"
            ));
        }

        if( !!$this->findOneBy(array('column'=>'nickname','value'=>$nickname),'nickname') ){
            return json_encode(array(
                "status"=> 0,
                "msg" => "注册昵称重复，请换一个试试！"
            ));
        }

        if(  strlen($password) < 6 ||  strlen($password) > 22 || !preg_match('/^\w{6,22}$/',$password) ){
            return json_encode(array(
                "status"=> 0,
                "msg" => "注册密码不符合格式!"
            ));
        }
        $date = date('Y-m-d H:i:s');
        $uid = $this->pdoAdd(array(
            'username'=>$username,
            'nickname'=>$nickname,
            'password'=>md5($password),
            'created' => $date,
            'logined' => $date
        ),'video_user');
        $userInfo = $this->findOneBy(array(
            'column'=>'uid',
            'value'=>$uid
        ),'*',true);
        $this->writeRedis($userInfo,null);
        // $this->_setIndexCookie($userInfo,true);
        //赠送金钱的入口
      //  $this->commonsendGift($uid,false,$this->_confAssoc['REGISTER_SEND_POINT']);
        //赠送座驾的入口
        $this->sendMotoring($uid,120010,15);
        //赠送3次抽奖,抽奖机会的key是hlottery_ary
        $this->_confAssoc['LOTTRY_STATUS'] && $this->_redisInstance->hset('hlottery_ary',$uid,3);
        //用户邀请注册处理接口,Author By D.C 2014.12.10
        $this->_userRegisterByInvitation($uid);

        //注册成功，redis开始计数
        if( $info == null ){
            $this->_addInvitation($invitationKey,$curIp,array('curDate'=>$curDay,'count'=>1  ));
        }else{
           // $info = json_decode($info,true);
            if( $info['curDate'] != $curDay ){//说明数据过期了，重置下
                $this->_addInvitation($invitationKey,$curIp,array('curDate'=>$curDay,'count'=> 1 ));
            }else{
                $info['count'] += 1;
                $this->_addInvitation($invitationKey,$curIp,$info );
            }
        }
        $this->checkAgent($uid);
        return json_encode(array(
            "status"=> 1,
            "msg" => "恭喜您注册成功！"
        ));
    }

    /**
     * 充值送时间期限的座驾，注意判断送的时候，该座驾是否已经过期了，如果过期，就在当前时间上来延长，如果没过期，就直接延长
     * @param $uid
     * @param $gid
     * @Author Orino
     */
    private function sendMotoring($uid,$gid,$days){
        if( !$this->_confAssoc['REG_SEND_STATUS'] || !$uid ){
            return false;
        }
        try{
          //  $row = $this->findOneBy(array('uid'=>$uid,'gid'=>$gid),'*',false,'video_pack');
            $stmt = $this->_dbInstance->prepare('select * from `video_pack` where uid='.intval($uid).' AND gid ='.intval($gid) );
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $days = $days*86400;
            if( empty($row) ){
                $this->pdoAdd(
                    array('uid'=>$uid,'gid'=>$gid,'num'=>1,'expires'=> ( NOW_TIME + $days )  )
                    ,'video_pack');
            }else{
                if(  $row['expires'] < NOW_TIME ){
                    $row['expires'] = NOW_TIME + $days;//天转化成秒
                }
                $sql = 'UPDATE `video_pack` SET `expires`=? WHERE uid=? AND gid=?';
                $stmt = $this->_dbInstance->prepare($sql);
                $stmt->execute(array($row['expires'],$row['uid'] ,$row['gid'] ));
            }
            return true;
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * 获取客户端IP地址，默认返回高级模式的返回IPV4地址数字，这样解决空间
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    private function getClientIp($type = 1,$adv=true) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);//过滤空格
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
    /**
     * 推广链接送钱
     * @param int $register_uid
     * @return bool
     * @Author Orino
     */
    private function _userRegisterByInvitation($register_uid = 0){

        //用户推广链接活动开关/注册失败
        //检查Cookie是否存在,并获取推广人ID
        //  $invitation_uid = $this->get('request')->cookies->get('invitation_uid');
        $invitation_uid = isset($_COOKIE['invitation_uid'])?$_COOKIE['invitation_uid']:null;
        $ips = $this->getClientIp();
        if( $this->_confAssoc['INVITATION_STATUS'] == 0 ){
            $this->pdoAdd(array(
                'pid'=> $invitation_uid,
                'uid'=> $register_uid,
                'reward'=> 0,
                'fromip'=> $ips,
                'fromtime'=> date('Y-m-d H:i:s'),
                'fromlink'=> isset($_COOKIE['invitation_refer'])?$_COOKIE['invitation_refer']:'',
            ),'video_user_invitation');
        }
        if( !$this->_confAssoc['INVITATION_STATUS'] || !$register_uid || !$invitation_uid ) return false;
          $redis = $this->getRedis();
        if( ! $this->_redisInstance->exists('huser_info:'.$invitation_uid) ){
            return false;
        }else{
            $user_info = $this->_redisInstance->hGetAll('huser_info:'.$invitation_uid);
        }
    //    $this->_redisInstance->hset('invitation', $invitation_uid, serialize(array('time'=>time(),'ips'=>$ips)));
        //奖励点数
        $addPoints = 100;
        $this->_points = $user_info['points'];//这是推广人的金钱数
        $this->sendGift($invitation_uid,$addPoints);
        $this->pdoAdd(array(
            'pid'=> $invitation_uid,
            'uid'=> $register_uid,
            'reward'=> $addPoints,
            'fromip'=> $ips,
            'fromtime'=> date('Y-m-d H:i:s'),
            'fromlink'=> isset($_COOKIE['invitation_refer'])?$_COOKIE['invitation_refer']:'',
        ),'video_user_invitation');
        //邀请成功，发送消息通知
        $this->_redisInstance->lPush('list_sendpnt',$invitation_uid.':'.$register_uid.':'.$addPoints);//推广者uid:推广注册uid:推广获取的钱数
        $this->_sendMsgBysystem($invitation_uid,'恭喜您成功邀请了一位新伙伴加入了蜜桃儿，并获得100个钻石的邀请奖励，要想获得更多奖励，请继续邀请您的亲朋好友们加入我们吧。');
        return true;
    }

    private function _addInvitation($key,$uid,$arr){
        $this->_redisInstance->hset($key,$uid,json_encode($arr));
    }
    private  function count_chinese_utf8($str) {
        if( $str == null) return 0;
        $arr = preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
        return count($arr);
    }

    /**
     * @Author Orino
     */
    public function rememberCheck(){
        $this->checkLogin($_REQUEST['v_remember_encrypt']);
        if( !! $this->_online  ){
            return json_encode(
                array(
                    'uid'=> $this->_online,
                    'phpsessid'=> session_id()
                )
            );
        }else{
            return json_encode(
                array(
                    'uid'=> 0,
                    'phpsessid'=> ''
                )
            );
        }
    }
}

if( isset($_GET['act']) && $_GET['act'] == 'register' ){
    echo (new LoginRoute($_W))->registerAct();
}elseif( isset($_GET['act']) && $_GET['act'] == 'remypasswd' ){
    echo (new LoginRoute($_W))->rememberCheck();
}else{
    echo (new LoginRoute($_W))->handler();
}
