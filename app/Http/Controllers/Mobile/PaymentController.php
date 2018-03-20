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
}