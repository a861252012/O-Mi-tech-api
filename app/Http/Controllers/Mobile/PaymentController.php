<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2017/1/18
 * Time: 11:17
 */

namespace App\Controller\Mobile;

use App\Models\ChargeList;
use App\Models\GiftActivity;
use App\Models\PayConfig;
use App\Models\PayOptions;
use App\Models\Recharge;
use App\Models\RechargeConf;
use App\Models\RechargeWhiteList;
use App\Models\Users;
use DB;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends MobileController
{
    public function __construct(Container $container)
    {
        $this->codeMsg = L('CHARGE.CODE_MSG');
        parent::__construct($container);
    }

    /**
     * 默认跳转页面
     **/
    public function action($action)
    {
        // 充钱成功后 检测用户的贵族状态

        if ($action != 'notice' && $action != 'moniCharge' && $action != 'callFailOrder' && $action != 'checkKeepVip') {
            if (true !== ($msg = Auth::check())) {
                return JsonResponse::create(['status' => 0, 'msg' => $msg]);
            }
        }

        $action = $action . 'Action';
        if (!method_exists($this, $action)) {
            return new Response('The method is not exists!', 500);
        } else {
            return $this->$action();
        }
    }

    /**
     * 充值页面
     * 移植自ChargeController
     */
    public function orderAction()
    {
        $uid = Auth::id();

        $user = $this->make('userServer')->getUserByUid($uid);
        $open = $this->make("redis")->hget("hconf", "open_pay") ?: 0;
        $origin = $this->request()->get('origin') ?: 12;
        $jwt = $this->make('JWTAuth');
        $jwt->getTokenFromRequest($this->request());
        $token = $jwt->token;
        if ($open) {
            $var['options'] = PayOptions::with('channels')->where('device', 'MOBILE')->orderBy('sid','desc')->get();
            $var['payConfig'] = PayConfig::where('open', 1)->get(['id', 'cid', 'bus', 'channel']);
            $var['origin'] = $origin;
            $jwt && $var['jwt'] = $token;
            //右边广告图
            $var['ad'] = '';
            $ad = $this->make('redis')->hget('img_cache', 3);// 获取右边的广告栏的数据
            if ($ad) {
                $a = json_decode($ad, true);
                $var['ad'] = $a[0];
            }
            return $this->render('Charge/order2', $var);
        }
        // 后台配置的充值限制条件群组信息
        $rechargeGroup = $this->make('redis')->get('recharge_group');
        if (!$rechargeGroup) {
            $group = RechargeConf::where('dml_flag', '!=', 3)->get()->toArray();
            // 格式化数组格式 array('[id]'=>array())
            $rechargeGroup = array();
            foreach ($group as $value) {
                $rechargeGroup[$value['auto_id']] = $value;
            }
            $this->make('redis')->set('recharge_group', json_encode($rechargeGroup));
        } else {
            //还原数组
            $rechargeGroup = json_decode($rechargeGroup, true);
        }


        //充值方法,会根据这个数来限制
        $RechargeTypes = 0;//默认没渠道

        // 1 验证白名单 和 黑名单 都在同一张表 type 0是白名单 1是黑名单
        $whiteList = RechargeWhiteList::where('uid', $uid)->where('dml_flag', '!=', 3)->first();

        // 如果在名单里面，就判断是白名单 还是 黑名单
        if ($whiteList) {

            if ($whiteList->type == 0) {
                //白名单根据后台白单配置
                $RechargeTypes = $rechargeGroup[1]['isopen'] < 2 ? intval($rechargeGroup[1]['isopen']) : $rechargeGroup[1]['recharge_type'];

            } else if ($whiteList->type == 1) {

                // 黑名单根据后台黑名单配置
                $RechargeTypes = $rechargeGroup[2]['isopen'] < 2 ? intval($rechargeGroup[2]['isopen']) : $rechargeGroup[2]['recharge_type'];
            }


            // 2 当用户没在名单中时就进行下面的验证
        } else {

            //因为要加上统计后台充值数据，所以改从video_recharge充值记录进行统计
            $paymoney = Recharge::where('uid', $uid)
                ->where('pay_status', 1)//统计已成功记录
                ->where(function ($query) {
                    $query->orWhere('pay_type', 1)->orWhere('pay_type', 4);//4=只统计银行充值和后台充值记录
                })->sum('paymoney');

            //将钻石转化成实际充值金额，目前算法 /10;
            $paymoney = $paymoney ?: 0;

            //循环匹配充值组
            foreach ($rechargeGroup as $rid => $val) {
                if ($rid < 3) continue; //黑名单、白名单不进入循环

                if ($val['recharge_min'] <= $paymoney && $paymoney <= $val['recharge_max']) {

                    //判断充值时间
                    $created = strtotime($user['created']);
                    $regTimeMax = time() - $val['reg_time_max'] * 86400;
                    $regTimeMin = time() - $val['reg_time_min'] * 86400;

                    //判断充值时间,充值金额权限
                    if ($created <= $regTimeMin && $created >= $regTimeMax) {
                        $RechargeTypes = $val['isopen'] < 2 ? intval($val['isopen']) : $val['recharge_type'];
                    }
                }

            }
        }

        // 没有充值的权限
        if ($RechargeTypes === 0) {
            return $this->render('Mobile/error', array('title' => L('CHARGE.ORDER.FAIL.NO_PRIVILEGE'), 'msg' => ''));

            // 联系客服
        } else if ($RechargeTypes === 1) {
            return $this->render('Mobile/error', array('title' => L('CHARGE.ORDER.TYPE1.0') . '<u style="color:blue;">' . L('CHARGE.ORDER.TYPE1.1') . '</u>！！！', 'msg' => ''));

        } else {
            //显示充值渠道
            $RechargeTypes = @unserialize($RechargeTypes);
            $var['active'] = GiftActivity::where('type', 2)->get();
            //充值方式数组
            $var['recharge_type'] = $RechargeTypes ?: array();
            //充值金额删选数组
            $var['recharge_money'] = $this->make('redis')->get('recharge_money') ?: json_encode(array());
            $var['user'] =& $user;
            $request = $this->request();
            $jwt = $this->make('JWTAuth');
            $jwt->getTokenFromRequest($request);
            $var['token'] = $jwt->token;
            return $this->render('Mobile/order', $var);
        }

    }

    /**
     * 微信充值页面
     * author: Young
     */
    public function wechatAction()
    {
        return $this->render('Mobile/wechat', array());
    }

    /**
     * 支付处理
     * 要组的格式
     * {
     *   "serviceCode": "",
     *   "version": "",
     *   "serviceType": "",
     *   "signType": "",
     *   "sign": "",
     *   "sysPlatCode": "",
     *   "sentTime": "",
     *   "expTime": "",
     *   "charset": "",
     *   "sMessageNo": "",
     *   "Datas": [
     *     {
     *       "dataNo": " FCDATA2014021014555633236522",
     *       "orderId": " H544514545544111",
     *       "accountChangeTime": "2014-08-09 09:54:02"
     *     }
     *   ]
     *  }
     *
     */
    public function payAction()
    {
        $amount = isset($_POST['price']) ? number_format(intval($_POST['price']), 2, '.', '') : 0;

        if (!$amount || $amount < 1) {
            $msg = L('CHARGE.PAY.WRONG_AMOUNT');
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }
        //获取下渠道
        $channel = $_POST['vipLevel'];
        $mode_type = $_POST['mode_type'];
        //ARD IOS
        $plat = isset($_POST['plat']) ? $_POST['plat'] : "";
        //判断下渠道存不存在
        if (empty($channel)) {
            $msg = L('CHARGE.PAY.WRONG_CHANEL');
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }
        //$amount = 1;
        $serviceCode = $this->container->config['config.PAY_SERVICE_CODE'];
        $version = $this->container->config['config.PAY_VERSION'];
        $serviceType = $this->container->config['config.PAY_SERVICE_TYPE'];
        $signType = $this->container->config['config.PAY_SIGNTYPE'];
        $sysPlatCode = $this->container->config['config.PAY_SYSPLATCODE'];
        $charset = $this->container->config['config.PAY_CHARSET'];
        $priviteKey = $this->container->config['config.PAY_PRIVATEKEY'];
        $remoteUrl = $this->container->config['config.PAY_CALL_URL'];

        //$randValue = mt_rand(1000, 9999);
        $sentTime = date('Y-m-d H:i:s');
        $expTime = '';
        $orderDate = date('YmdHis');

        $date_array = explode(" ", microtime());
        $milliseconds = $date_array[1] . ($date_array[0] * 10000);
        $milliseconds = explode(".", $milliseconds);
        $randValue = substr($milliseconds[0], -4);

        //唯一订单号
        $sMessageNo = $serviceCode . $orderDate . $randValue;

        // $sMessageNo = $this->checkOrderNu($serviceCode,$sMessageNo);

        //通知地址
        //获取下当前域名
//        $noticeUrl = $GLOBALS['CUR_URL'] . $this->container->config['config.PAY_NOTICE_URL'];
        $noticeUrl = $this->container->config['config.PAY_NOTICE_URL'];
        $returnUrl = $GLOBALS['CUR_URL'] . $this->container->config['config.PAY_REBACK_URL'];
        $username = $this->userInfo['username'];
        $isMobile = "true";
        $Datas = array(
            array(
                'dataNo' => $plat . 'FCDATA' . $orderDate . $randValue,
                'amount' => $amount,
                'noticeUrl' => $noticeUrl,
                'returnUrl' => $returnUrl,
                'remark' => $username,
                'channel' => "",
                'vipLevel' => $channel,
                'bankCode' => "",
                'lan' => "",
                'currency' => "",
                'isMobile' => $isMobile,
            )
        );
        $sign_str = "";
        foreach ($Datas[0] as $v) {
            $sign_str .= $v;
        }
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        $sign = MD5($serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset . $sMessageNo . $sign_str .
            $priviteKey);

        $postdataArr = array(
            'serviceCode' => $serviceCode,
            'version' => $version,
            'serviceType' => $serviceType,
            'signType' => $signType,
            'sign' => $sign,
            'sysPlatCode' => $sysPlatCode,
            'sentTime' => $sentTime,
            'expTime' => $expTime,
            'charset' => $charset,
            'sMessageNo' => $sMessageNo,
            'Datas' => $Datas
        );
        $postdata = json_encode($postdataArr);

        //记录下数据库
//        $uid = $this->request()->getSession()->get(self::SEVER_SESS_ID);
        $uid = Auth::id();//todo recheck session
        $tradeno = $sMessageNo;
        switch (substr($tradeno, 0, 3)) {
            case "ARD":
                $origin = 22;
                break;
            case "IOS":
                $origin = 32;
                break;
            default:
                $origin = 12;
        }
//        ChargeList::create(
//            array(
//                'uid' => $uid,
//                'ctime' => date('Y-m-d H:i:s'),
//                'status' => 0,
//                'del' => 0,
//                'paymoney' => $amount,
//                'points' => ceil($amount * 10),
//                'tradeno' => $sMessageNo,
//                'ttime' => date('Y-m-d H:i:s'),
//                'postdata' => $postdata,
//                'channel' => $channel,
//                'mode_type' => $mode_type,
//                'pay_type' => 1,
//                'origin' => $origin
//            )
//        );
        Recharge::create(
            array(
                'uid' => $uid,
                'created' => date('Y-m-d H:i:s'),
                'pay_status' => 0,// 刚开始受理
                'pay_type' => 1, // 银行充值
                'del' => 0,
                'paymoney' => $amount,
                'points' => ceil($amount * 10),
                'order_id' => $sMessageNo,
                'postdata' => $postdata,
                'nickname' => $this->userInfo['nickname'],
                'channel' => $channel,
                'mode_type' => $mode_type,
                'origin' => $origin
            )
        );

        $rtn = array(
            'postdata' => $postdata,
            'remoteUrl' => $remoteUrl,
        );
        return $this->render('Mobile/pay', $rtn);
    }

    public function paybakAction()
    {
        die();
        $amount = isset($_POST['price']) ? number_format(intval($_POST['price']), 2, '.', '') : 0;

        if (!$amount || $amount < 1) {
            $msg = L('CHARGE.PAY.WRONG_AMOUNT');
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }
        //获取下渠道
        $channel = $_POST['vipLevel'];
        $mode_type = $_POST['mode_type'];
        //ARD IOS
        $plat = isset($_POST['plat']) ? $_POST['plat'] : "";
        //判断下渠道存不存在
        if (empty($channel)) {
            $msg = L('CHARGE.PAY.WRONG_CHANEL');
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }
//        $channel = $channelList[$orginalChannel];
        //渠道转化为数字入库
//        if ($orginalChannel == 'xs') {
//            $channelId = 1;
//        } elseif ($orginalChannel == 'hx') {
//            $channelId = 2;
//        } elseif ($orginalChannel == 'sf') {
//            $channelId = 3;
//        } else {
//            $channelId = 0;
//        }
        //$amount = 1;
        $serviceCode = $this->container->config['config.PAY_SERVICE_CODE'];
        $version = $this->container->config['config.PAY_VERSION'];
        $serviceType = $this->container->config['config.PAY_SERVICE_TYPE'];
        $signType = $this->container->config['config.PAY_SIGNTYPE'];
        $sysPlatCode = $this->container->config['config.PAY_SYSPLATCODE'];
        $charset = $this->container->config['config.PAY_CHARSET'];
        $priviteKey = $this->container->config['config.PAY_PRIVATEKEY'];
        $remoteUrl = $this->container->config['config.PAY_CALL_URL'];

        //$randValue = mt_rand(1000, 9999);
        $sentTime = date('Y-m-d H:i:s');
        $expTime = '';
        $orderDate = date('YmdHis');

        $date_array = explode(" ", microtime());
        $milliseconds = $date_array[1] . ($date_array[0] * 10000);
        $milliseconds = explode(".", $milliseconds);
        $randValue = substr($milliseconds[0], -4);

        //唯一订单号
        $sMessageNo = $serviceCode . $orderDate . $randValue;

        // $sMessageNo = $this->checkOrderNu($serviceCode,$sMessageNo);

        //通知地址
        //获取下当前域名
//        $noticeUrl = $GLOBALS['CUR_URL'] . $this->container->config['config.PAY_NOTICE_URL'];
        $noticeUrl = $this->container->config['config.PAY_NOTICE_URL'];
        $returnUrl = $GLOBALS['CUR_URL'] . $this->container->config['config.PAY_REBACK_URL'];
        $username = $this->userInfo['username'];
        $Datas = array(
            array(
                'dataNo' => $plat . 'FCDATA' . $orderDate . $randValue,
                'amount' => $amount,
                'noticeUrl' => $noticeUrl,
                'returnUrl' => $returnUrl,
                'remark' => $username,
                'channel' => "",
                'vipLevel' => $channel,
                'bankCode' => ""
            )
        );
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        $sign = MD5($serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset . $sMessageNo
            . $Datas[0]['dataNo'] . $Datas[0]['amount'] . $Datas[0]['noticeUrl'] . $Datas[0]['returnUrl'] . $Datas[0]['remark'] . $channel .
            $priviteKey);
        $postdataArr = array(
            'serviceCode' => $serviceCode,
            'version' => $version,
            'serviceType' => $serviceType,
            'signType' => $signType,
            'sign' => $sign,
            'sysPlatCode' => $sysPlatCode,
            'sentTime' => $sentTime,
            'expTime' => $expTime,
            'charset' => $charset,
            'sMessageNo' => $sMessageNo,
            'Datas' => $Datas
        );
        $postdata = json_encode($postdataArr);

        //记录下数据库
//        $uid = $this->request()->getSession()->get(self::SEVER_SESS_ID);
        $uid = Auth::id();//todo recheck session
        $tradeno = $sMessageNo;
        switch (substr($tradeno, 0, 3)) {
            case "ARD":
                $origin = 21;
                break;
            case "IOS":
                $origin = 22;
                break;
            default:
                $origin = 1;
        }
//        ChargeList::create(
//            array(
//                'uid' => $uid,
//                'ctime' => date('Y-m-d H:i:s'),
//                'status' => 0,
//                'del' => 0,
//                'paymoney' => $amount,
//                'points' => ceil($amount * 10),
//                'tradeno' => $sMessageNo,
//                'ttime' => date('Y-m-d H:i:s'),
//                'postdata' => $postdata,
//                'channel' => $channel,
//                'mode_type' => $mode_type,
//                'pay_type' => $origin
//            )
//        );

        $rtn = array(
            'orderId' => $sMessageNo,
            'gotourl' => ' /charge/translate'
        );
        //跳转页面需要的信息，设置session数据
        $this->request()->getSession()->set(
            'currentOrderInfo',
            array(
                'postdata' => $postdata,
                'remoteUrl' => $remoteUrl,
            )
        );


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remoteUrl); //设置请求的URL
        $header[] = "isMobile:Android";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['postDatas' => $postdata]);
        $output = curl_exec($ch);
        $errstr = curl_error($ch);
        curl_close($ch);


        //验证下签名
        if ($errstr) {
            $loginfo = date('Y-m-d H:i:s') . "\n 充提第一次请求 网络错误: \n" . $errstr . "\n";
        } else {
            $loginfo = date('Y-m-d H:i:s') . "\n 充提第一次请求: \n" . $postdata . "\n";
        }
        $logPath = BASEDIR . '/app/logs/mcharge_' . date('Y-m-d') . '.log';
        $this->logResult($loginfo, $logPath);

        return new JsonResponse(array('status' => 0, 'msg' => $output));
    }

    /**
     * todo 未使用
     */
    public function translateAction()
    {
//        $rtn = $this->request()->getSession()->get('orderInfo');
        $redis = $this->make('redis');
        $rtn = $redis->hgetall('currentOrderInfo:' . Auth::id());
        if ($rtn) {
            //销毁session，保证页面的提交，每次是新的订单
//            $this->request()->getSession()->set('orderInfo', null);
            $redis->del('currentOrderInfo:' . Auth::id());//改为redis
        } else {
            $rtn = array();
        }
        return $this->render('Mobile/pay', $rtn);
    }

    /**
     * 检查订单号唯一性
     * @Author Orino
     */
    public function checkOrderNu($serviceCode, $sMessageNo)
    {
        $sql = 'SELECT t.* FROM `video_charge_list` t WHERE  AND tradeno = "' . $sMessageNo . '"';
        //强制查询主库
        $ret = DB::select('/*' . MYSQLND_MS_MASTER_SWITCH . '*/' . $sql);
        if (!$ret) {
            return $sMessageNo;
        } else {
            $sMessageNo = $serviceCode . date('YmdHis') . mt_rand(1000, 9999);
            return $this->checkOrderNu($serviceCode, $sMessageNo);
        }
    }

    /**
     * todo 未使用
     * 通知地址
     */
    public function noticeAction()
    {
        //获取下数据
        $postResult = file_get_contents("php://input");
        //拿到通知的数据
        if (!$postResult) {
            return new JsonResponse(array('status' => 1, 'msg' => 'no data input!'));
        }

        $jsondatas = json_decode($postResult, true);
        $len = $jsondatas['Datas'] ? count($jsondatas['Datas']) : 0;
        if (json_last_error() > 0 || $len == 0) {
            return new JsonResponse(array('status' => 1, 'msg' => 'json string ie error!'));
        }
        //记录下日志
//        $this->_appRoot = $this->get('kernel')->getRootDir();
        $logPath = BASEDIR . '/app/logs/mcharge_' . date('Y-m-d') . '.log';
        $loginfo = date('Y-m-d H:i:s') . "\n传输的数据记录: \n" . $postResult . "\n";
        $tradeno = $jsondatas['Datas'][0]['orderId'];//拿出1个账单号
        //验证下签名
        if (!$this->checkSign($jsondatas)) {
            $signError = "订单号：" . $tradeno . "\n签名没有通过！\n";
            $loginfo .= $signError;
            $this->logResult($loginfo, $logPath);
            return new JsonResponse(array('status' => 1, 'msg' => $signError));
        }
        if ($len == 1) {
            $paytradeno = $jsondatas['Datas'][0]['payOrderId'];
            $money = $jsondatas['Datas'][0]['amount'];
            $chargeResult = $jsondatas['Datas'][0]['result'];
            $channel = $jsondatas['Datas'][0]['channel'];
            $complateTime = $jsondatas['Datas'][0]['complateTime'];
        } else {
            for ($i = 0; $i < $len; $i++) {
                $paytradeno = $jsondatas['Datas'][$i]['payOrderId'];
                $money = $jsondatas['Datas'][$i]['amount'];
                $chargeResult = $jsondatas['Datas'][$i]['result'];
                $channel = $jsondatas['Datas'][$i]['channel'];
                $complateTime = $jsondatas['Datas'][$i]['complateTime'];
                //存在同一个v项目订单号对应2个以上的财务财务订单号
                if ($chargeResult == 2 && !empty($paytradeno)) {
                    break;
                }
            }
        }
        return $this->orderHandler($tradeno, $paytradeno, $loginfo, $logPath, $money, $chargeResult, $channel, $complateTime);
    }

    /**
     * 验证下签名是否通过
     */
    private function checkSign($postResult)
    {
        //接口名称
        $serviceCode = $postResult['serviceCode'];
        //版本
        $version = $postResult['version'];
        //接口类型
        $serviceType = $postResult['serviceType'];
        //平台代码
        $sysPlatCode = $postResult['sysPlatCode'];
        //发送时间
        $sentTime = $postResult['sentTime'];

        $expTime = $postResult['expTime'];
        //编码格式
        $charset = $postResult['charset'];
        //messageNo
        $sMessageNo = $postResult['sMessageNo'];
        //数据包
        $Datas = $postResult['Datas'];
        //私钥
        $priviteKey = $this->container->config['config.PAY_PRIVATEKEY'];
        //传过来的sign
        $oldSign = $postResult['sign'];
        //生成签名
        $newDatas1 = $Datas;
        $newDatas = $newDatas1[0];
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        $str = $serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset . $sMessageNo;
        foreach ($newDatas as $value) {
            $str .= $value;
        }
//            $newDatas['dataNo'] . $newDatas['orderId'] . $newDatas['payOrderId'] . $newDatas['amount'] . $newDatas['result'] . $newDatas['remark'] . $priviteKey);
        $str .= $priviteKey;
        //echo $serviceCode.$version.$serviceType.$sysPlatCode.$sentTime.$expTime.$charset.$sMessageNo.$newDatas['dataNo'].$newDatas['orderId'].$newDatas['payOrderId'].$newDatas['amount'].$newDatas['result'].$newDatas['remark'].$priviteKey;exit;
        return MD5($str) == $oldSign;
    }
    /**
     * 更新后台用户看到的信息
     */
    public function updateReChargeInfo($chargeStatus, $points, $username, $tradeno, $paytradeno, $uid)
    {
        //成功支付订单，状态为1，其他为2
        $chargeStatus = $chargeStatus ? 1 : 2;

        $flag = DB::table('video_recharge')->where('order_id', $tradeno)->update(array(
            'pay_status' => $chargeStatus,
            'pay_id' => $paytradeno,
        ));
        if ($flag == false) {
            $paymoney = round($points/10,1);
            DB::table('video_recharge')->insertGetId(array(
                'points' => $points,
                'paymoney' => $paymoney,
                'created' => date('Y-m-d H:i:s'),
                'nickname' => $username,
                'order_id' => $tradeno,
                'pay_status' => $chargeStatus,
                'pay_type' => 1,
                'pay_id' => $paytradeno,
                'uid' => $uid,
            ));
        }

    }

    /**
     * todo 未使用
     * 活动调用方法
     */
    public function doHuodong($money, $uid, $tradeno)
    {
        //活动接口url
        $activityUrl = $this->container->config['config.VFPHP_HOST_NAME'] . $this->container->config['config.ACTIVITY_URL'];
        //获取下token值
        $token = $this->generateUidToken($uid);
        //活动名称
        $activityName = $this->container->config['config.ACTIVITY_NAME'];
        //echo $token;exit;
        //发送的数据
        $activityPostData = array(
            'ctype' => $activityName, //活动类型
            'money' => $money, //充值的金额
            'uid' => $uid, //用户id
            'vsign' => $this->make('config')->get('config.VFPHP_SIGN'),
            'token' => $token, //口令牌
            'order_num' => $tradeno, //定单号
        );


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $activityUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        $postData = http_build_query($activityPostData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $activityResult = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);

        //记录下活动日志
        $hdlogPath = BASEDIR . '/app/logs/mfirstcharge_' . date('Y-m-d') . '.log';
        $chargeHuodong = "充值活动结果" . $tradeno . "\n";
        $chargeHuodong .= $activityResult;
        try {
            $activityResult = json_decode($activityResult, true);
            if ($curl_errno == 0 && is_array($activityResult)) {
                $this->logResult($chargeHuodong . "\n", $hdlogPath);
            } else {
                $hdlogPath2 = BASEDIR . '/app/logs/mfirstcharge_error_' . date('Y-m-d') . '.log';
                file_put_contents($hdlogPath2, $postData . PHP_EOL, FILE_APPEND);
            }
        } catch (\Exception $e) {
            $hdlogPath2 = BASEDIR . '/app/logs/mfirstcharge_error_' . date('Y-m-d') . '.log';
            unset($activityPostData['token']);
            file_put_contents($hdlogPath2, $postData . PHP_EOL, FILE_APPEND);
        }
        return $activityResult;
    }

    /**
     * 返回给充提的结果
     */
    public function back2Charge($chargeResult, $tradeno, $paytradeno)
    {
        //数据统计好后，根据状态来返回结果
        if ($chargeResult == 0) {
            $chargeResult2 = L('CHARGE.BACK2CHARGE.0');
        } elseif ($chargeResult == 1) {
            $chargeResult2 = L('CHARGE.BACK2CHARGE.1');
        } elseif ($chargeResult == 2 && !empty($paytradeno)) {
            $chargeResult2 = L('CHARGE.BACK2CHARGE.2');
        } elseif ($chargeResult == 3) {
            $chargeResult2 = L('CHARGE.BACK2CHARGE.3');
        }
        //  return '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">订单号：' . $tradeno . $chargeResult2 . ' \n';
        return $tradeno . $chargeResult2 . $paytradeno;
    }

    /**
     * todo
     * 写一个模拟充提中心的中转
     */
    public function moniChargeAction()
    {
        if (!getenv('STRESS_ENV')) {
            exit;
        }
        $priviteKey = $this->container->config['config.PAY_PRIVATEKEY'];
        //$postResult = file_get_contents("php://input");
        $jsondatas = json_decode($_POST['postDatas'], true);
        //var_dump($jsondatas);exit;
        switch ($jsondatas['version']) {
            case 1:
                break;
                break;
            case 1.3;
                break;
        }

        echo "转化成充提中心给我的json格式\n";
        //取出postresult里面需要的数据
        $channel = $jsondatas['Datas'][0]['vipLevel'];
        $datas = array(
            array(
                'dataNo' => $jsondatas['Datas']['0']['dataNo'],
                'orderId' => $jsondatas['sMessageNo'],
                'payOrderId' => mt_rand(10000000, 99999999),
                'amount' => $jsondatas['Datas']['0']['amount'],
                'noticeUrl' => $jsondatas['Datas']['0']['noticeUrl'],
                'returnUrl' => $jsondatas['Datas']['0']['returnUrl'],
                'result' => 2,
                'remark' => '',
                'complateTime' => date('Y-m-d H:i:s'),
                'channel' => $channel
            )
        );
        //$newDatas = $datas[0];
        $Datas = $datas;
        //生成下签名
        $serviceCode = $this->container->config['config.PAY_SERVICE_CODE'];
        $version = $this->container->config['config.PAY_VERSION'];
        $serviceType = $this->container->config['config.PAY_SERVICE_TYPE'];
        $signType = $this->container->config['config.PAY_SIGNTYPE'];
        $sysPlatCode = $this->container->config['config.PAY_SYSPLATCODE'];
        $charset = $this->container->config['config.PAY_CHARSET'];
        $priviteKey = $this->container->config['config.PAY_PRIVATEKEY'];
        $remoteUrl = $this->container->config['config.PAY_CALL_URL'];
//
//        $serviceCode = 'FC0023';
//        $version = '1';
//        $serviceType = '3';
//        $signType = 'md5';
//        $sysPlatCode = 'V';
//        $charset = 'utf-8';
        $sentTime = $jsondatas['sentTime'];
        $expTime = '';
        $sMessageNo = $jsondatas['sMessageNo'];
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
//        $sign = MD5($serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset .$sMessageNo
//            . $newDatas['dataNo'] . $newDatas['orderId'] . $newDatas['payOrderId'] .
//            $newDatas['amount'] . $newDatas['result'] . $newDatas['remark'] . $priviteKey);
        $newDatas = $Datas[0];
        $str = $serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset . $sMessageNo;
        foreach ($newDatas as $value) {
            $str .= $value;
        }
        $str .= $priviteKey;
        $sign = MD5($str);
        $postdataArr = array(
            'serviceCode' => $serviceCode,
            'version' => $version,
            'serviceType' => $serviceType,
            'signType' => $signType,
            'sysPlatCode' => $sysPlatCode,
            'charset' => $charset,
            'sentTime' => $jsondatas['sentTime'],
            'expTime' => $expTime,
            'sign' => $sign,
            'sMessageNo' => $sMessageNo,
            'Datas' => $datas
        );
        /*$remoteUrl = 'http://www.vf.com/charge/notice';
        $rtn = array(
            'postdata' => json_encode($postdataArr),
            'remoteUrl' => $remoteUrl
        );*/
        //echo '<pre>', json_encode($postdataArr);
        $payNoticeUrl = $this->container->config['config.VFPHP_HOST_NAME'] . $this->container->config['config.PAY_NOTICE_URL'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payNoticeUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdataArr));
        $server_output = curl_exec($ch);
        curl_close($ch);
        return new JsonResponse(array('status' => 1, 'msg' => $server_output));
    }

    /**
     * @return JsonResponse
     * @Author Orino
     */
    public function callFailOrderAction()
    {
        $payOrderJson = file_get_contents("php://input");
        $type = 2;
//        $this->_appRoot = $this->get('kernel')->getRootDir();
        $logPath = BASEDIR . '/app/logs/mcharge_' . date('Y-m-d') . '_' . $type . '.log';
        $this->logResult($payOrderJson, $logPath);
        if (!$payOrderJson) {
            return new JsonResponse(array('status' => 1, 'msg' => L('CHARGE.FAIL_ORDER.INVALID_DATA')));
        }
        $payOrderJson = json_decode($payOrderJson, true);
        if (!$this->verifyUidToken($payOrderJson['uid'], $payOrderJson['token'])) {
            return new JsonResponse(array('status' => 1, 'msg' => L('CHARGE.FAIL_ORDER.UNAUTHORIZED')));
        }
        return $this->comHandler($payOrderJson, $type);
    }

    /**
     * 公共的处理函数，log文件类型
     * “正常支付流程”是app/logs/mcharge_20150728.log,
     * ”已完成支付“调用已充值是app/logs/mcharge_20150728_3.log,
     * “后台调用支付”是app/logs/mcharge_20150728_2.log
     * @param $payOrderJson
     * @param int $type
     * @return JsonResponse
     * @Author Orino
     */
    private function comHandler($payOrderJson, $type = 1)
    {
        $tradeno = $payOrderJson['orderId'];
        $paytradeno = $payOrderJson['payOrderId'];
        $money = $payOrderJson['amount'];
//        $this->_appRoot = $this->get('kernel')->getRootDir();
        $logPath = BASEDIR . '/app/logs/mcharge_' . date('Y-m-d') . '_' . $type . '.log';
        $loginfo = json_encode($payOrderJson);
        $chargeResult = $payOrderJson['result'];
        $complateTime = $payOrderJson['complateTime'];
        $channel = '';
        return $this->orderHandler($tradeno, $paytradeno, $loginfo, $logPath, $money, $chargeResult, $channel, $complateTime);
    }

    public function checkKeepVipAction()
    {
        $msg = file_get_contents("php://input");
//        $this->_appRoot = $this->get('kernel')->getRootDir();
        $logPath = BASEDIR . '/app/logs/mcharge_' . date('Y-m-d') . '_checkKeepVip.log';
        $this->logResult($msg, $logPath);
        if (!$msg) {
            return new JsonResponse(array('status' => 1, 'msg' => L('CHARGE.CHECK_KEEP_VIP.INVALID_DATA')));
        }
        $msg = json_decode($msg, true);
        // 充钱成功后 检测用户的贵族状态
        $uinfo = Users::find($msg['uid']);
        $this->checkUserVipStatus($uinfo);
        return new JsonResponse(array('status' => 0, 'msg' => 'It is ok！'));
    }

    /**
     * todo 未使用
     * 通过订单号查询
     * @return Response
     * @Author Orino
     */
    public function checkChargeAction()
    {
        $orderId = isset($_GET['orderId']) ? $_GET['orderId'] : '';
        if (!$orderId) {
            return new JsonResponse(array('status' => 1, 'msg' => L('CHARGE.CHECK_CHARGE.ORDER_NOT_EXIST')));
        }
        $sql = 'SELECT * FROM `video_charge_list` WHERE  tradeno ="' . $orderId . '" FOR UPDATE';
        //强制查询主库
        $ret = DB::select('/*' . MYSQLND_MS_MASTER_SWITCH . '*/' . $sql);
        $ret = (array)$ret[0];// stdClass 转数组
        if (!$ret) {
            return new JsonResponse(array('status' => 1, 'msg' => L('CHARGE.CHECK_CHARGE.ORDER_NOT_EXIST')));
        }
        if ($ret['status'] == 2) {
            return new JsonResponse(array('status' => 0, 'msg' => L('CHARGE.CHECK_CHARGE.SUCCESS')));
        }
        if ($ret['status'] == 3) {
            return new JsonResponse(array('status' => 0, 'msg' => L('CHARGE.CHECK_CHARGE.FAIL')));
        }
        $serviceCode = $this->container->config['config.PAY_FIND_CODE'];//查询接口的名称FC0029
        $version = $this->container->config['config.PAY_VERSION'];
        $serviceType = $this->container->config['config.PAY_SERVICE_TYPE'];
        $signType = $this->container->config['config.PAY_SIGNTYPE'];
        $sysPlatCode = $this->container->config['config.PAY_SYSPLATCODE'];
        $charset = $this->container->config['config.PAY_CHARSET'];
        $priviteKey = $this->container->config['config.PAY_PRIVATEKEY'];
        $randValue = mt_rand(1000, 9999);
        $sentTime = date('Y-m-d H:i:s');
        $expTime = '';
        $sMessageNo = $serviceCode . date('YmdHis') . $randValue;//随意给的，只是让校验产生随机性质
        $Datas = array(
            array(
                "dataNo" => $orderId,
                "orderId" => $orderId,
                "payOrderId" => "",
                "type" => 1 //查询接口类型
            )
        );
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        $sign = MD5(
            $serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset . $sMessageNo .
            $Datas[0]['dataNo'] . $Datas[0]['orderId'] . $Datas[0]['payOrderId'] . $Datas[0]['type']
            . $priviteKey
        );
        $postdataArr = array(
            'serviceCode' => $serviceCode,
            'version' => $version,
            'serviceType' => $serviceType,
            'signType' => $signType,
            'sign' => $sign,
            'sysPlatCode' => $sysPlatCode,
            'sentTime' => $sentTime,
            'expTime' => $expTime,
            'charset' => $charset,
            'sMessageNo' => $sMessageNo,
            'Datas' => $Datas
        );
//        $this->_appRoot = $this->get('kernel')->getRootDir();
        $logPath = BASEDIR . '/app/logs/mcharge_' . date('Y-m-d') . '_3.log';

        $ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $this->container->config['config.PAY_CALL_URL']); //设置请求的URL
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdataArr));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $output = curl_exec($ch);
        $errstr = curl_error($ch);
        curl_close($ch);
        $this->logResult($this->container->config['config.PAY_CALL_URL'] . PHP_EOL . 'output' . $output . PHP_EOL . 'error' . $errstr, $logPath);
        if (!empty($errstr)) {
            return new JsonResponse(array('status' => 1, 'msg' => L('CHARGE.CHECK_CHARGE.API_FAIL') . $errstr));
        }
        $output = json_decode($output, true);
        if (!isset($output['Datas'])) {
            return new JsonResponse(array('status' => 1, 'msg' => L('CHARGE.CHECK_CHARGE.API_DATA_ERROR')));
        }
        //测试自己内部程序的数据
//          $output['Datas'] = array(array(
//               'result'=> 2,
//               'payOrderId'=> 'FC000V201507110028063180016',
//               'amount'=> '100.00',
//               'orderId'=> $orderId,
//           ));

        $len = count($output['Datas']);
        $payOrderJson = '';
        for ($i = 0; $i < $len; $i++) {
            //存在同一个v项目订单号对应2个以上的财务财务订单号
            if ($output['Datas'][$i]['result'] == 2 && !empty($output['Datas'][$i]['payOrderId'])) {
                $payOrderJson = $output['Datas'][$i];
                break;
            }
        }
        //校验到成功的订单号，应该走原来通知回调的逻辑
        if (!!$payOrderJson) {
            return $this->comHandler($payOrderJson, 3);
        } else {
            $sts = $output['Datas'][0]['result'];
            //订单都没有提交到那边去
            if ($sts === '') {
                $sts = -1;
            }
//
//            /**
//             * 写入到后台recharge表中的充值记录，用于获取到财务订单号
//             */
//            if($sts != '') {
//                $user = Users::find($ret['uid']);
//                $this->updateReChargeInfo('', $output['Datas'][0]['amount'], $user['username'], $orderId, $output['Datas'][0]['payOrderId'], $user['uid']);
//            }
            // 更新订单状态
            ChargeList::where('id', $ret['id'])->update(['status' => $sts]);

            $testPath = BASEDIR . '/app/logs/test_' . date('Y-m-d') . '.log';
            $testInfo = "getmypid:" . getmypid() . "checkCharge Recharge update codeMsg: $sts  payOrderJson: $payOrderJson \n";
            $this->logResult($testInfo, $testPath);

            $msg = isset($this->codeMsg[$sts]) ? $this->codeMsg[$sts] : '失败';
            return new JsonResponse(array('status' => 0, 'msg' => '该订单充值' . $msg . '！'));
        }
    }
}