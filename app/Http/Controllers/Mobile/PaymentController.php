<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2017/1/18
 * Time: 11:17
 */

namespace App\Http\Controllers\Mobile;

use App\Libraries\ErrorResponse;
use App\Libraries\SuccessResponse;
use App\Models\ChargeList;
use App\Models\GiftActivity;
use App\Models\PayConfig;
use App\Models\PayOptions;
use App\Models\Recharge;
use App\Models\RechargeConf;
use App\Models\RechargeWhiteList;
use App\Models\Users;
use App\Services\User\UserService;
use DB;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends MobileController
{
    public function __construct(Container $container)
    {
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
    public function order()
    {
        $uid = Auth::id();

        $user = resolve(UserService::class)->getUserByUid($uid);
        $open = resolve("redis")->hget("hconf", "open_pay") ?: 0;
        $request = resolve('request');
        $origin = $request->get('origin',12);
        $token =  Auth::getToken();
        if ($open) {
            $var['options'] = PayOptions::with('channels')->where('device', 'MOBILE')->orderBy('sid','desc')->get();
            $var['payConfig'] = PayConfig::where('open', 1)->get(['id', 'cid', 'bus', 'channel']);
            $var['origin'] = $origin;
            $var['jwt'] = $token;
            //右边广告图
            $var['ad'] = '';
            $ad = $this->make('redis')->hget('img_cache', 3);// 获取右边的广告栏的数据
            if ($ad) {
                $a = json_decode($ad, true);
                $var['ad'] = $a[0];
            }
            $var['pay'] = 2;
            return SuccessResponse::create($var);
        }

        // 没有充值的权限
        if (resolve('chargeGroup')->close($uid))
            return ErrorResponse::create(array('title' => '尊敬的用户，您好，恭喜您成为今日幸运之星，请点击在线客服领取钻石，感谢您的支持与理解！', 'msg' => ''));

        if (resolve('chargeGroup')->customer($uid)) {
            return ErrorResponse::create(array('title' => '需要充值请<u style="color:blue;">联系客服</u>！！！', 'msg' => ''));
        }

        $var['active'] = GiftActivity::where('type', 2)->get();
        //充值方式数组
        //充值方式数组
        $var['recharge_type'] = resolve('chargeGroup')->channel($uid);
        //充值金额删选数组
        $var['recharge_money'] = $this->make('redis')->get('recharge_money') ?: json_encode(array());
        $var['user'] =& $user;
        $var['token'] = $token;
        $var['pay'] = 1;
        return SuccessResponse::create($var);

    }

    /**
     * 微信充值页面
     * author: Young
     */
    public function wechat()
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
    public function pay()
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