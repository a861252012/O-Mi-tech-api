<?php

namespace App\Http\Controllers;

use App\Events\Active;
use App\Models\ChargeList;
use App\Models\GiftActivity;
use App\Models\PayConfig;
use App\Models\PayOptions;
use App\Models\Recharge;
use App\Models\RechargeConf;
use App\Models\RechargeWhiteList;
use App\Models\Users;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Libraries\ErrorResponse;
use App\Libraries\SuccessResponse;

class ChargeController extends Controller
{
    /**
     * dm对象
     */
    private $_gm;

    /**
     * 根路径
     */
    private $_appRoot;
    /**
     * @var DataModel
     */
    private $_dmodel;

    private $codeMsg = array(
        -1 => '未知',
        0 => '已接受',
        1 => '处理中',
        2 => '处理成功',
        3 => '处理失败'
    );

    /**
     * 充值页面
     * 于20160817重构多充值方式开发需求(旧版已注释)
     * @author dc
     * @version 20160817
     * @return \Core\Response
     */
    public function order()
    {
        $uid = Auth::id();
        $origin = $this->request()->get('origin') ?: 12;
        $user = $this->make('userServer')->getUserByUid($uid);

        $var['user'] = $user;

        $open = $this->make("redis")->hget("hconf", "open_pay") ?: 0;
        if ($open) {
//            $jwt = $this->make('JWTAuth');
//            $jwt->getTokenFromRequest($this->request());
//            $token = $jwt->token;
            $var['options'] = PayOptions::with('channels')->where('device', 'PC')->get();
            $var['payConfig'] = PayConfig::where('open', 1)->get(['id', 'cid', 'bus', 'channel']);
            $var['origin'] = $origin;
         //   $jwt && $var['jwt'] = $token;
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
            return ErrorResponse::create(array('title' => '<a id="live800iconlink" href="javascript:void(0)" onclick="return openChat(this) " lim_company="410769">需要充值请<u style="color:blue;">联系客服</u>！！！</a>', 'msg' => ''));
        }

        $var['active'] = GiftActivity::where('type', 2)->get();

        //右边广告图
        $var['ad'] = '';
        $ad = $this->make('redis')->hget('img_cache', 3);// 获取右边的广告栏的数据
        if ($ad) {
            $a = json_decode($ad, true);
            $var['ad'] = $a[0];
        }

        //充值方式数组
        $var['recharge_type'] = resolve('chargeGroup')->channel($uid);

        //检查用户登录权限
        $var['user_login_asset'] = true;

        //最小充值限制
        $var['user_money_min'] = config('const.user_points_min') / 10;

        //充值金额删选数组
        $var['recharge_money'] = $this->make('redis')->get('recharge_money') ?: json_encode(array());
        $var['pay']=1;
        return SuccessResponse::create($var);
    }

    /**
     * 删除订单操作
     */
    public function del()
    {
        $lid = $this->request()->get('lid');
        $chargeObj = Recharge::where('id', $lid)->where('uid', Auth::id())->first();

        //判断下这条记录是不是这个用户的
        if (!$chargeObj) {
            return new ErrorResponse(array(
                'info' => '这条记录不是你的!',
                'ret' => false
            ));
        }
        //执行删除操作
        $chargeObj->del = 1;
        $chargeObj->save();

        return new SuccessResponse(array(
            'info' => '删除成功！',
            'ret' => true
        ));
    }

    public function notice2()
    {
        //获取下数据
        $postdata = $_POST['postdata'];
        //拿到通知的数据
        if (empty($postdata) || strpos($postdata, '.') === false) {
            return JsonResponse::create(['status' => -1, 'msg' => 'invalid input!']);
        }
        list($jsonData, $sign) = explode('.',$postdata);
        $jsonData = base64_decode($jsonData);
        $data = json_decode($jsonData,true);
        if (json_last_error() > 0) {
            return JsonResponse::create(['status' => -1, 'msg' => 'JSON format error']);
        }
        //记录下日志
        $logPath = BASEDIR . '/app/logs/charge2_' . date('Y-m-d') . '.log';
        $loginfo = date('Y-m-d H:i:s') . "\n传输的数据记录: \n" . $jsonData . "\n";

        $tradeno = $data['order_id'];//拿出1个账单号
        //验证签名
        $key = resolve('siteService')->config('back_pay_sign_key');
        if (hash_hmac('sha256', $jsonData, $key) !== $sign) {
            $signError = "订单号：" . $tradeno . "\n签名没有通过！\n";
            $loginfo .= $signError;
            $this->logResult($loginfo, $logPath);
            return new JsonResponse(array('status' => -1, 'msg' => $signError));
        }
        $paytradeno = $data['pay_id'];
        $money = $data['money'];
        $chargeResult = $data['result'];
        $channel = $data['channel'];
        $complateTime = $data['complateTime'];

        return $this->orderHandler($tradeno, $paytradeno, $loginfo, $logPath, $money, $chargeResult, $channel, $complateTime);


    }

    /**
     * @param $tradeno v项目订单号
     * @param $paytradeno 财务订单号
     * @param $loginfo 日志
     * @param $logPath 日志的路径
     * @param $money 金钱数目
     * @param $chargeResult 结果
     * @return JsonResponse
     * 充提返回值：-1未知 0已接受 1处理中 2处理成功
     * @Author Orino
     */
    private function orderHandler($tradeno, $paytradeno, $loginfo, $logPath, $money, $chargeResult, $channel = '', $complateTime = '')
    {
        //开启事务
        try {
            DB::beginTransaction();
            $sql = 'SELECT t.* FROM `video_recharge` t WHERE t.pay_type in(1,50) AND t.pay_status < 2 AND order_id = "' . $tradeno . '" LIMIT 1 FOR UPDATE';
            //强制查询主库
            //$stmt = DB::select('/*' . MYSQLND_MS_MASTER_SWITCH . '*/' . $sql);
            $stmt = DB::select($sql);

            if (empty($stmt)) {
                $dealOrCannotFind = "订单号：" . $tradeno . "\n数据已处理完毕，请查看'充值记录！'\n";
                $loginfo .= $dealOrCannotFind;
                $this->logResult($loginfo, $logPath);
                return new JsonResponse(array('status' => 0, 'msg' => $dealOrCannotFind));
            }
            $stmt = (array)$stmt[0];
            //第一步，写日志
            $loginfo .= "订单号：" . $tradeno . " 收到，并且准备更新：\n";
            $points = $stmt['points'];
            DB::table('video_recharge')->where('id', $stmt['id'])->update(array(
                'paymoney' => $money,
                'pay_status' => $chargeResult,
                'ttime' => $complateTime,
                'pay_id' => $paytradeno
            ));

            $chargeStatus = false;//成功状态标记位
            if ($chargeResult == 2 && !empty($paytradeno)) {

                $rs = DB::table('video_user')->where('uid', $stmt['uid'])->increment('points', $points);
                if ($rs) {
                    $chargeStatus = true;//成功状态为1
                } else {
                    $loginfo .= '充值成功，增加的钱数:' . $points . ' 失败';
                }
            }

            DB::commit();

            //刷新redis钻石
            if ($chargeStatus) {
                $userObj = DB::table('video_user')->where('uid', $stmt['uid'])->first();//Users::find($stmt['uid']);
                $loginfo .= '增加的钱数: paymoney '.$money.' points:' . $points . ' 最终的钱数:' . $userObj->points;
                $this->make('redis')->hincrby('huser_info:' . $stmt['uid'], 'points', $points);
            }

            // 充钱成功后 检测用户的贵族状态
            $uinfo = Users::find($stmt['uid']);
            resolve('userGroupServer')->checkUserVipStatus($uinfo);

        } catch (\Exception $e) {
            $this->logResult("订单号：" . $tradeno . " 事务结果：" . $e->getMessage() . "\n", $logPath);
            DB::rollback();
            return new JsonResponse(array('status' => 1, 'msg' => '程序内部异常'));
        }

        //首次充值时间
        if ($chargeStatus)  resolve('charge')->chargeAfter($stmt['uid']);

        //第二步，更新数据
        $loginfo .= "订单号：" . $tradeno . " 数据处理成功！\n";
        //成功才触发充值自动送礼
        if ($chargeStatus && resolve('siteService')->config('activity_open')) {
            //活动的调用写到对应的方法内
            resolve('active')->doHuodong($money, $stmt['uid'], $tradeno);
        }

        //封装下结果给充提
        $rtn2back = $this->back2Charge($chargeResult, $tradeno, $paytradeno);
        Log::info("返回给充提中心的结果：$rtn2back");
        return new JsonResponse(array('status' => 0, 'msg' => $rtn2back));

    }

    protected function sendCurlRequest($url, $data)
    {
        $ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $output = curl_exec($ch);
        $errstr = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        return ['data' => $output, 'errstr' => $errstr, 'errno' => $curl_errno];
    }

    /**
     * 返回给充提的结果
     */
    public function back2Charge($chargeResult, $tradeno, $paytradeno)
    {
        //数据统计好后，根据状态来返回结果
        if ($chargeResult == 0) {
            $chargeResult2 = ' 已接受！';
        } elseif ($chargeResult == 1) {
            $chargeResult2 = ' 处理中！';
        } elseif ($chargeResult == 2 && !empty($paytradeno)) {
            $chargeResult2 = ' 处理成功！';
        } elseif ($chargeResult == 3) {
            $chargeResult2 = ' 处理失败！';
        }
        //  return '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">订单号：' . $tradeno . $chargeResult2 . ' \n';
        return $tradeno . $chargeResult2 . $paytradeno;
    }

    public function pay2()
    {
        $option_id = $this->request()->get('oid');
        $cid = $this->request()->get('cid');
        $origin = $this->request()->get('origin') ?: 12;
        $remark = $this->request()->get('remark') ?: '';
        $option = PayOptions::find($option_id);
        $errors = [];
        if (!$option || !$option->exists) {
            $errors[] = '金额错误，请返回重试';
            return ErrorResponse::create(compact('errors'));
        }
        $amount = number_format(intval($option['rmb']), 2, '.', '');
        if (!$amount || $amount < 1) {
            $errors[] = '请输入正确的金额!';
            return ErrorResponse::create(compact('errors'));
        }
        //获取下渠道
        $channel = PayConfig::where('cid', $cid)->where('open', 1)->first();
        //判断下渠道存不存在
        if (!$channel || !$channel->exists) {
            $errors[] = '请选择充值渠道!';
            return ErrorResponse::create(compact('errors'));
        }
        $rtn = [];
        if (empty($errors)) {
            try {
                $remoteUrl = $this->container->config['config.BACK_PAY_CALL_URL'];
                $notice = $this->container->config['config.BACK_PAY_NOTICE_URL'];
                $key = $this->container->config['config.BACK_PAY_SIGN_KEY'];
                $time = time();
                $timeHex = dechex($time);
                $orderId = $this->generateOrderId();
                $postdata = json_encode([
                    'order_id' => $orderId,
                    'money' => $amount,
                    'channel' => $cid,
                    'notice' => $notice,
                    'isMobile' => $origin >= 20 && $origin <= 39,
                    'origin' => $origin,
                    'plat_code' => $this->container->config['config.BACK_PLAT_CODE'],
                    'uid' => Auth::id(),
                    'user_name' => $this->userInfo['username'],
                    'remark' =>$remark,
                    't' => $timeHex
                ]);
                $sign = hash_hmac('sha256', $postdata, $key);
                //跳转页面需要的信息，设置session数据
                $rtn = [
                    'postdata' => collect([base64_encode($postdata), $sign])->implode('.'),
                    'remoteUrl' => $remoteUrl,
                ];
                $record = Recharge::create([
                    'uid' => Auth::id(),
                    'created' => date('Y-m-d H:i:s', $time),
                    'pay_status' => 0,// 刚开始受理
                    'pay_type' => 1, // 银行充值
                    'del' => 0,
                    'paymoney' => $amount,
                    'points' => $option->points,
                    'order_id' => $orderId,
                    'postdata' => $postdata,
                    'nickname' => $this->userInfo['nickname'],
                    'channel' => $cid,
                    'origin' => $origin
                ]);
            } catch (\Exception $e) {
                $rtn['errors'] = ['创建订单失败'];
                return ErrorResponse::create( $rtn);
            }
        } else {
            $rtn['errors'] = $errors;
        }
        return SuccessResponse::create($rtn);
    }

    public function generateOrderId()
    {
        return date('ymdHis') . mt_rand(10, 99) . sprintf('%08s', strrev(Auth::id())) . '';
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
     * }
     *
     */
    public function pay()
    {
        $amount = isset($_POST['price']) ? number_format(intval($_POST['price']), 2, '.', '') : 0;
        $origin = $this->request()->get('origin') ?: 12;

        if (!$amount || $amount < 1) {
            $msg = '请输入正确的金额!';
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }
        $fee = 0;
        if($giftactive = GiftActivity::query()->where('moneymin',intval($amount))->first())    $fee = $giftactive->fee;

        //获取下渠道
        $channel = $_POST['vipLevel'];
        $mode_type = $_POST['mode_type'];
        //判断下渠道存不存在
        if (empty($channel)) {
            $msg = '请选择充值渠道!';
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }

        $postdata = resolve('charge')->postData($amount,$channel);

        //记录下数据库
        $uid = Auth::id();
        Recharge::create(
            array(
                'uid' => $uid,
                'created' => date('Y-m-d H:i:s'),
                'pay_status' => 0,// 刚开始受理
                'pay_type' => 1, // 银行充值
                'del' => 0,
                'paymoney' => $amount,
                'points' => ceil($amount * 10)+$fee,
                'order_id' => resolve('charge')->getMessageNo(),
                'postdata' => $postdata,
                'nickname' => Auth::user()['username'],
                'channel' => $channel,
                'mode_type' => $mode_type,
                'origin' => $origin
            )
        );

        $rtn = array(
            'orderId' => resolve('charge')->getMessageNo(),
            'gotourl' => ' /charge/translate'
        );
        //跳转页面需要的信息，设置session数据

        Session()->put(
            'orderInfo',
            array(
                'postdata' => $postdata,
                'remoteUrl' => resolve('charge')->remote(),
            )
        );
        return new JsonResponse(array('status' => 0, 'msg' => $rtn));
    }

    /**
     * @return Response
     * @author Orino
     */
    public function translate()
    {
        $rtn = Session('orderInfo');
        if (!!$rtn) {
            //销毁session，保证页面的提交，每次是新的订单
            Session()->put('orderInfo', null);
        } else {
            $rtn = array();
        }
        return SuccessResponse::create($rtn);
    }


//    /**
//     * 写一个模拟充提handler处理
//     */
//    public function moniHandler()
//    {
//
//        $payOrderJson=array(
//            'orderId'=>'FC0028201507112130551649',
//            'payOrderId'=>'FC0028201507112130551649',
//            'amount'=>'500',
//            'result'=>'2'
//        );
//
//        $this->comHandler($payOrderJson);
//    }

    /**
     * 通知地址
     */
    public function notice()
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
        Log::info("传输的数据记录: " . $postResult);

        $tradeno = $jsondatas['Datas'][0]['orderId'];//拿出1个账单号
        //验证下签名
        if (!resolve('charge')->checkSign($jsondatas)) {
            $signError = "订单号：" . $tradeno . "\n签名没有通过！\n";
            Log::info($signError);
            return new JsonResponse(array('status' => 1, 'msg' => date('Y-m-d H:i:s')." \n".$postResult."\n".$signError));
        }
        for ($i = 0; $i < $len; $i++) {
            $paytradeno = $jsondatas['Datas'][$i]['payOrderId'];
            $money = $jsondatas['Datas'][$i]['amount'];
            $chargeResult = $jsondatas['Datas'][$i]['result'];
            $channel = $jsondatas['Datas'][$i]['channel'];
            $complateTime = $jsondatas['Datas'][$i]['complateTime'];
            //存在同一个v项目订单号对应2个以上的财务财务订单号
            if ($chargeResult == 2 && !empty($paytradeno))    break;
        }

        //test code
//        $tradeno='0000000000000000000000001';
//        $paytradeno='83170079';
//        $money=10;
//        $chargeResult=2;
//        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '.log';
//        $loginfo = date('Y-m-d H:i:s') . "\n传输的数据记录: \n" . "\n";
//        $channel = '';
//        $complateTime = ''.date('Y-m-d H:i:s');
        return $this->orderHandler($tradeno, $paytradeno, $loginfo="", $logPath="", $money, $chargeResult, $channel,$complateTime);
    }

    /**
     * 写一个模拟充提中心的中转
     */
    public function moniCharge(Request $request)
    {
        if (!config('app.debug')) {
            exit;
        }
        //2017.2.23 改为配置文件绝对地址
        $orderID = $request->get('orderid');
        $amount = $request->get('amount');
        $payNoticeUrl = route('charge_notice');
        $postdataArr = resolve('charge')->getTestNoticeData($orderID,$amount);
        $send_result = $this->sendCurlRequest($payNoticeUrl, $postdataArr);
        $server_output = $send_result['data'];
        return new Response(json_encode($server_output));
    }

    /**
     * @return JsonResponse
     * @Author Orino
     */
    public function callFailOrder()
    {
        $payOrderJson = file_get_contents("php://input");
        $type = 2;
        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '_' . $type . '.log';
        $this->logResult($payOrderJson, $logPath);
        if (!$payOrderJson) {
            return new JsonResponse(array('status' => 1, 'msg' => '传入的数据存在问题'));
        }
        $payOrderJson = json_decode($payOrderJson, true);
        if (!$this->verifyUidToken($payOrderJson['uid'], $payOrderJson['token'])) {
            return new JsonResponse(array('status' => 1, 'msg' => '非法操作！'));
        }
        return $this->comHandler($payOrderJson, $type);
    }

    /**
     * 公共的处理函数，log文件类型
     * “正常支付流程”是app/logs/charge_20150728.log,
     * ”已完成支付“调用已充值是app/logs/charge_20150728_3.log,
     * “后台调用支付”是app/logs/charge_20150728_2.log
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
        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '_' . $type . '.log';
        $loginfo = json_encode($payOrderJson);
        $chargeResult = $payOrderJson['result'];
        $complateTime = $payOrderJson['complateTime'];
        $channel = '';
        return $this->orderHandler($tradeno, $paytradeno, $loginfo, $logPath, $money, $chargeResult, $channel, $complateTime);
    }

    public function checkKeepVip()
    {
        $msg = file_get_contents("php://input");
        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '_checkKeepVip.log';
        $this->logResult($msg, $logPath);
        if (!$msg) {
            return new JsonResponse(array('status' => 1, 'msg' => '传入的数据存在问题'));
        }
        $msg = json_decode($msg, true);
        // 充钱成功后 检测用户的贵族状态
        $uinfo = Users::find($msg['uid']);
        resolve('userGroupServer')->checkUserVipStatus($uinfo);
        return new JsonResponse(array('status' => 0, 'msg' => 'It is ok！'));
    }

    /**
     * 通过订单号查询
     * @return Response
     * @Author Orino
     */
    public function checkCharge()
    {
        $orderId = isset($_GET['orderId']) ? $_GET['orderId'] : '';
        if (!$orderId) {
            return new JsonResponse(array('status' => 1, 'msg' => '该订单号不存在！'));
        }
        $sql = 'SELECT * FROM `video_recharge` WHERE  order_id ="' . $orderId . '"';
        //强制查询主库
        $ret = DB::select($sql);
        //$ret = DB::select('/*' . MYSQLND_MS_MASTER_SWITCH . '*/' . $sql);
        $ret = (array)$ret[0];// stdClass 转数组
        if (!$ret) {
            return new JsonResponse(array('status' => 1, 'msg' => '该订单号不存在！'));
        }
        if ($ret['pay_status'] == 2) {
            return new JsonResponse(array('status' => 0, 'msg' => '该订单号已经成功支付,请返回会员中心的"充值记录"查看！'));
        }
        if ($ret['pay_status'] == 3) {
            return new JsonResponse(array('status' => 0, 'msg' => '该订单号支付已经失败,请返回会员中心的"充值记录"查看！'));
        }
        $POST_Array = resolve('charge')->getFindRequest($orderId);
        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '_3.log';

        $send_result = $this->sendCurlRequest($this->container->config['config.PAY_CALL_URL'], json_encode($POST_Array));
        $output = $send_result['data'];
        $errstr = $send_result['errstr'];

        $this->logResult($this->container->config['config.PAY_CALL_URL'] . PHP_EOL . 'output' . $output . PHP_EOL . 'error' . $errstr, $logPath);
        if (!empty($errstr)) {
            return new JsonResponse(array('status' => 1, 'msg' => '充提查询接口出问题：' . $errstr));
        }
        $output = json_decode($output, true);
        if (!isset($output['Datas'])) {
            return new JsonResponse(array('status' => 1, 'msg' => '充提返回数据有问题'));
        }
        //测试自己内部程序的数据
        /*   $output['Datas'] = array(array(
               'result'=> 2,
               'payOrderId'=> 'FC000V201507110028063180016',
               'amount'=> '100.00',
               'orderId'=> $orderId,
           ));
        */
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
        }
        $sts = $output['Datas'][0]['result'];
        $updat_data = array();
        //订单都没有提交到那边去
        if ($sts === '') {
            $updat_data['pay_status'] = -1;
        } else {
            /**
             * 写入到recharge表中的充值记录，用于获取到财务订单号处理掉单的情况
             */
            $updat_data['pay_status'] = $sts;
            $updat_data['pay_id'] = $output['Datas'][0]['payOrderId'];
        }

        // 更新订单状态
        $msg = $this->codeMsg[$sts] ?? '失败';
        return new JsonResponse(array('status' => 0, 'msg' => '该订单充值' . $msg . '！'));
    }
}
