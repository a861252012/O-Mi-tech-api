<?php
/**
 * @apiDefine Charge 充值功能
 */
/** @noinspection PhpUndefinedClassInspection */

namespace App\Http\Controllers;

use App\Constants\BankCode;
use App\Facades\SiteSer;
use App\Http\Requests\Charge\ChargePay;
use App\Libraries\ErrorResponse;
use App\Libraries\SuccessResponse;
use App\Models\GiftActivity;
use App\Models\PayAccount;
use App\Models\PayGD;
use App\Models\Recharge;
use App\Models\RechargeMoney;
use App\Models\Users;
use App\Services\Charge\OnePayService;
use App\Services\Charge\ChargeService;
use App\Services\FirstChargeService;
use App\Services\User\UserService;
use App\Traits\Commons;
use App\Services\UserAttrService;
use DB;
use Hashids\Hashids;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ChargeController extends Controller
{
    use Commons;

    const NOTICE_TIMEOUT_GD = 30;
    const CHANNEL_GD_ALI = 7;
    const CHANNEL_GD_BANK = 8;
    const ORDER_REPEAT_LIMIT_GD = 60;
    const BLOCK_MSG = '尊敬的用户，您好，您今日的充值申请已达上限，请点击在线客服，让我们协助您，感谢您的支持与理解！';

    /* One Pay */
    const CHANNEL_ONE_PAY = 13;

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
     * @return ErrorResponse
     */
    public function order(Request $request)
    {
        $uid = Auth::id();
        $user = resolve(UserService::class)->getUserByUid($uid);
        unset($user->password);
        unset($user->p2p_password);
        unset($user->trade_password);
        $client = $request->is('api/m/*') ? 2 : 1;

        $token = $client == 2 ? Auth::getToken() : "";

        $var['user'] = $user;

        // 没有充值的权限
        if (resolve('chargeGroup')->close($uid)) {
            return ErrorResponse::create(array('title' => self::BLOCK_MSG, 'msg' => ''));
        }

        if (resolve('chargeGroup')->customer($uid)) {
            return ErrorResponse::create(array('title' => '需要充值请联系客服！！！', 'msg' => ''));
        }

        $var['active'] = GiftActivity::where('type', 2)->get();

        //充值方式数组
        //$var['recharge_type'] = resolve('chargeGroup')->channel($uid);
        $A_pay_channel = resolve('chargeGroup')->channel($uid);
        $A_pay_channel2 = array();
        foreach($A_pay_channel as $S_pay_channel){
            $S_check=0;
            if(strpos($S_pay_channel['name'],'银行卡')!==false){
                $bankCard = PayAccount::whereNull('deleted_at')->withTrashed()->get()->toArray();
                if(empty($bankCard)){
                    $S_check++;
                }
            }
            if($S_check==0){
                array_push($A_pay_channel2,$S_pay_channel);
            }
        }
        $var['recharge_type'] = $A_pay_channel2;
        //银行卡

        //检查用户登录权限
        $var['user_login_asset'] = true;

        //最小充值限制
        $var['user_money_min'] = config('const.user_points_min') / 10;

        //充值金额删选数组
        $recharge_money = $this->getRechargeMoney();
        $temp = [];
        foreach ($recharge_money as $k => $value) {
            if (isset($value->client) && $value->client == $client) {
                array_push($temp, $value);
            }
        }

        $var['recharge_money'] = json_encode($temp);
        $var['token'] = $token;
        $var['pay'] = 1;
        $var['giftShow'] = resolve(FirstChargeService::class)->isShowFirstGiftIcon($uid);
        $var['giftRemainingTime'] = resolve(FirstChargeService::class)->countRemainingTime($uid);

        return SuccessResponse::create($var);
    }

    private function getRechargeMoney()
    {
        $redis = $this->make('redis');
        $data = $redis->get('recharge_money');
        $json = [];
        if (!$data) {
            $data = RechargeMoney::all(['recharge_min','recharge_max','recharge_type','client']);
            if ($data) {
                $json = $data->toJson();
                $redis->set('recharge_money', $json);
                $data = $redis->get('recharge_money');
            }
        }
        $json = json_decode($data);
        return $json;
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
            //return new ErrorResponse('这条记录不是你的');
            return JsonResponse::create(['status' => 0, 'msg' => '这条记录不是你的']);
        }
        //执行删除操作
        $chargeObj->del = 1;
        $chargeObj->save();

        //return new SuccessResponse('删除成功');
        return JsonResponse::create(['status' => 1, 'msg' => '删除成功']);
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
     * @api {post} /api/charge/pay 執行支付動作
     * @apiGroup Charge
     * @apiName pay
     * @apiVersion 1.0.0
     *
     * @apiParam {Int} price 價格
     * @apiParam {Int} vipLevel 充值渠道
     * @apiParam {Int} mode_type
     * @apiParam {String} name
     *
     * @apiError (Error Status) 1 请输入正确的金额
     * @apiError (Error Status) 1 请选择充值渠道
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccessExample {json} 成功回應
     * {
    "status": 0,
    "data": {
    "postdata": "{\"serviceCode\":\"FC0045\",\"version\":\"1.5\",\"serviceType\":\"03\",\"signType\":\"md5\",\"sysPlatCode\":\"V\",\"sentTime\":\"2020-04-28 18:34:32\",\"expTime\":\"\",\"charset\":\"utf-8\",\"sMessageNo\":\"FC00450045394915880700729692\",\"Datas\":[{\"dataNo\":\"FCDATA0045394915880700729694\",\"amount\":\"1000.00\",\"noticeUrl\":\"http:\\\/\\\/www.ymrenn.com\\\/api\\\/charge\\\/notice\",\"returnUrl\":\"\\\/charge\\\/reback\",\"remark\":\"rand9551107869@x.com\",\"channel\":\"\",\"vipLevel\":\"1\",\"bankCode\":\"\",\"lan\":\"\",\"currency\":\"\",\"isMobile\":\"false\"}],\"sign\":\"73fe05fbe29c5688ed49812df0e4f93e\"}",
    "orderId": "FC00450045394915880700729692",
    "remoteUrl": "https:\/\/gopay.pay-sin.com\/pay"
    },
    "msg": ""
    }
     */
    public function pay(ChargePay $request)
    {
        Log::debug('金流支付request: ' . var_export($request->all(), true));

        if ($request->price < 1) {
            $msg = '请输入正确的金额!';
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }

        $chargeService = resolve(ChargeService::class);

        // IP 黑名單為最高優先權，不用考慮後台的充值黑名單設定
        $enable_block = SiteSer::globalSiteConfig('enable_recharge_block_ip') == "1";
        if ($enable_block) {
            $ip = $this->getIp();
            if ($chargeService->isIpBlocked($ip)) {
                return new JsonResponse(array('status' => 1, 'msg' => self::BLOCK_MSG));
            }
        }

        // 擋掉没有充值的权限
        $uid = Auth::id();
        if (resolve('chargeGroup')->close($uid)) {
            return new JsonResponse(array('status' => 1, 'msg' => self::BLOCK_MSG));
        }

        $fee = 0;
        if ($giftactive = GiftActivity::query()->where('moneymin', $request->price)->first()) {
            $fee = $giftactive->fee;
        }

        //获取下渠道
        //防止带入恶意参数
        $channel = $this->request()->input('vipLevel');
        $mode_type = $this->request()->input('mode_type');
        //判断下渠道存不存在
        if (empty($channel)) {
            $msg = '请选择充值渠道!';
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }
        //判断下渠道是否開放
        $valid = resolve('chargeGroup')->validChannel($uid, $channel);
        if (!$valid) {
            $msg = '充值渠道未开放!';
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }

        // 檢查每日請求上限 (未完成訂單 < 5)
        if ($chargeService->isDailyLimitReached($uid)) {
            return new JsonResponse(array('status' => 1, 'msg' => self::BLOCK_MSG));
        }

        $origin = $this->getClient();

        /* 回傳資料處理動作 */
        $act = '1';

        $orderId = '';

        /** 古都 */
        if (((int) $mode_type) === static::CHANNEL_GD_ALI || ((int) $mode_type) === static::CHANNEL_GD_BANK) {
            $chargeService->incrDailyLimit($uid);
            return $this->processGD([
                'money' => $amount,
                'uid' => Auth::id(),
                'channel' => $channel,
                'comment' => $this->request()->get('name'),
                'mode_type' => $mode_type,
                'fee' => $fee,
                'origin' => $origin,
            ]);
        }

        switch ($mode_type) {
            case self::CHANNEL_ONE_PAY:
                $act = '3';
                $onePayService = resolve(OnePayService::class);
                $onePayService->genOrder();
                $orderId = $onePayService->getOrderId();
                $postdata = $onePayService->pay($request->price);

                if (!empty($onePayService->getStatus())) {
                    $msg = '请联系客服，错误代码 ' . $onePayService->getStatus();
                }

                break;
            default:
                $charge = resolve('charge');
                $orderId = $charge->getMessageNo();
                $postdata = $charge->postData($request->price, $channel);
                $remoteUrl = resolve('charge')->remote();
        }

        //记录下数据库
        $uid = Auth::id();
        Recharge::create(
            array(
                'uid'        => $uid,
                'created'    => date('Y-m-d H:i:s'),
                'pay_status' => 0,// 刚开始受理
                'pay_type'   => Recharge::PAY_TYPE_CHONGTI, // 银行充值
                'del'        => 0,
                'paymoney'   => $request->price,
                'points'     => ceil($request->price * 10) + $fee,
                'order_id'   => $orderId,
                'postdata'   => $postdata,
                'nickname'   => Auth::user()['nickname'],
                'channel'    => $channel,
                'mode_type'  => $mode_type,
                'origin'     => $origin,
                'ip'         => $this->getIp(),
            )
        );
        $chargeService->incrDailyLimit($uid);

        $rtn = array(
            'postdata'  => $postdata,
            'orderId'   => $orderId,
            'remoteUrl' => $remoteUrl ?? '',
            'act'       => $act,
        );

        Log::channel('charge')->info($rtn);

        //將首充禮包icon改回不顯示
        resolve(UserAttrService::class)->set($uid, 'first_gift', 1);

        return new JsonResponse(array('status' => 0, 'data' => $rtn, 'msg' => $msg ?? ''));
    }

    public function exchange(Request $request)
    {
        //修复注入漏洞
        $status = $request->input('status')??'';
        $orderid = $request->input('orderid')??'';
        //去除％和0x攻击
        $orderid = preg_replace('/%|0x|SELECT|FROM/', ' ', $orderid);
        if (!$orderid) {
            return new JsonResponse(array('status' => 2, 'msg' => '没有订单号！'));
        }

        //强制查询主库
        $ret = Recharge::where('order_id', $orderid)->where('pay_status', 4)->first();

        if (!$ret) {
            return new JsonResponse(array('status' => 3, 'msg' => '该订单号不存在！'));
        }
        if(empty($status)){
            return new JsonResponse(array('status' => 2, 'msg' => '状态不正确！'));
        }
        if($status!=2){
            $ret->pay_status=3;
            $ret->save();
            return new JsonResponse(array('status' => 1, 'msg' => '兑换失败！'));
        }
        /*
        进入交易
        点数充值
        更改订单状态
        送出交易
        更新redis点数
        */
        $loginfo = "";
        //开启事务
        try {
            DB::beginTransaction();
            //订单状态改变
            $ret->pay_status=2;
            $ret->save();
            //第一步，写日志
            $loginfo .= "订单号：" . $orderid . " 收到，并且准备兑换钻石：\n";

            $rs = DB::table('video_user')->where('uid', $ret->uid)->increment('points', $ret->points);
            $userObj = DB::table('video_user')->where('uid', $ret->uid)->first();//Users::find($stmt['uid']);
            //$platform_find = DB::table('video_platforms')->where('origin',$ret->origin)->first();
            $platform_find = $this->make('redis')->get('hplatform:'.$ret->origin);

            $order = array(
                'uid'=>$ret->uid,
                'points'=>$ret->points,
                'score'=>$ret->points*$platform_find->rate,
                'platform_id'=>$platform_find->platform_id,
                'origin'=>$ret->origin
            );
            //兌換紀錄
            DB::table('video_platform_exchange')->insert($order);

            $loginfo .= '会员编号:'.$userObj->uid.' 增加的钻石: points:' . $ret->points . ' 最终的钻石:' . $userObj->points;
            //刷新redis钻石
            resolve(UserService::class)->getUserReset($ret->uid);

            DB::commit();
            Log::channel('charge')->info($loginfo);
            return new JsonResponse(array('status' => 0, 'msg' => '兑换成功！'));
        } catch (\Exception $e) {
            Log::channel('charge')->info("订单号：" . $orderid . " 事务结果：" . $e->getMessage() . "\n");
            DB::rollback();
            return new JsonResponse(array('status' => 1, 'msg' => '程序内部异常'));
        }
        /*$tradeno = $orderid;
        $paytradeno = null;
        $money = $ret->points;
        $loginfo = null;
        $chargeResult = $status;
        $complateTime = date('Y-m-d H:i:s', time());
        $channel = '';
        return $this->orderHandler($tradeno, $paytradeno, $loginfo, $logPath = "", $money, $chargeResult, $channel, $complateTime);*/
    }

    private function getClient()
    {
        $client = $this->request()->headers->get('client', 12);
        if ($client){
            switch ($client) {
                case "1001":
                    $origin = 22;
                    break;
                case "1002":
                    $origin = 32;
                    break;
                default:
                    $origin = 12;
            }
        }else{
            $origin = $this->request()->get('origin');
        }
        return $origin;
    }

    /**
     * @param $data
     * @return JsonResponse
     */
    public function processGD($data)
    {
        $money = $data['money'];
        $comment = $data['comment'];
        $channel = $data['channel'];
        $modeType = $data['mode_type'];
        $fee = $data['fee'];
        $origin = $data['origin'];
        $order_id = 'GD' . $this->generateOrderId();
        $uid = $data['uid'];
        if ($modeType == static::CHANNEL_GD_ALI && empty($comment)) {
            //支付宝转账需要输入名字
            return JsonResponse::create(['status' => 1, 'msg' => '请输入名称']);
        } elseif ($modeType == static::CHANNEL_GD_BANK) {
            //银行转账生成唯一备注
            //$comment = (new Hashids($uid, 4, 'abcdefghijklmnopqrstuvwxyz1234567890'))
            //    ->encode(mt_rand(1, 1000000));
            $comment = substr(md5(uniqid(md5(microtime(true),true))),0,4);
        }
        $obj = PayGD::query()
            ->whereNotIn('status', [2, 4])->where('charge_amount', $money)
            ->where('comment', $comment)
            ->orderBy('id','desc')
            ->first();
        if ($obj && strtotime($obj['created_at']) + static::ORDER_REPEAT_LIMIT_GD * 60 > time()) {
            $err = "1小时内，不能提同一金额，同一姓名的订单";
            return JsonResponse::create(['status' => 1, 'msg' => $err]);
        }
        $account = PayAccount::query()->orderByRaw('rand()')->first();

        $postdata = [
            'mode_type' => $modeType,
            'pay_id' => $order_id,
            'money' => $money,
            'remark' => $comment,
            'bank' => $account->card_name,
            'rec_name' => $account->name,
            'account' => $account->account,
        ];

        $recharge = Recharge::create([
                'uid' => $uid,
                'created' => date('Y-m-d H:i:s'),
                'pay_status' => 0,// 刚开始受理
                'pay_type' => 1, // 银行充值
                'del' => 0,
                'paymoney' => $money,
                'points' => ceil($money * 10) + $fee,
                'order_id' => $order_id,
                'postdata' => json_encode($postdata),
                'nickname' => $this->userInfo['nickname'],
                'channel' => $channel,
                'mode_type' => $modeType,
                'origin' => $origin,
                'ip' => $this->getIp(),
            ]);

        PayGD::create([
            'charge_id' => $recharge->id,
            'order_id' => $order_id,
            'comment' => $comment,
            'charge_amount' => $money,
            'uid' => $uid,
            'site_id' => SiteSer::siteId(),
        ]);

        $rtn = [
            'orderId' => $order_id,
            'postdata' => $postdata,
            'remoteUrl' => '/charge/showGD',
        ];

        if ($this->getClient() != 12) {
            $rtn = $postdata;
        }
        return JsonResponse::create(['status' => 0, 'data' => $rtn]);
    }

    public function generateOrderId()
    {
        return date('ymdHis') . mt_rand(10, 99) . sprintf('%08s', strrev(Auth::id())) . '';
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function testNoticeGD(Request $request)
    {
        $amount = $request->get('amount');
        $comment = $request->get('comment');

        $data = [
            'serial_num' => 'gd' . mt_rand(100000, 999999),
            'bank_id' => 13,
            'amount' => $amount,
            'usercard_num' => '',
            'incomebankcard' => mt_rand(100000, 999999),
            'fee' => 0,
            'pay_type' => "",
            'processtime' => date('Y-m-d H:i:s'),
            'comment' => $comment,
        ];
        $key = SiteSer::config('pay_gd_key');
        $verifymd5 = MD5($data['amount'] . $data['comment'] . $key);
        $this->sendCurlRequest(route('gd_notice'), ['data' => json_encode((object)$data), 'verifymd5' => $verifymd5]);
        return new JsonResponse(['data' => 'success']);
    }

    protected function sendCurlRequest($url, $data)
    {
        $ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen(json_encode($data))));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $output = curl_exec($ch);
        $errstr = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        return ['data' => $output, 'errstr' => $errstr, 'errno' => $curl_errno];
    }

    /**
     * @return Response
     */
    public function noticeGD()
    {
        $data = $this->request()->input('data');
        $verifymd5 = $this->request()->input('verifymd5');
        Log::channel('charge')->info('古都通知:' . $data . '\t' . $verifymd5);
        $obj = json_decode($data);
        if (!$obj) {
            return $this->gdResponse($obj, 44);
        }
        $serial_num = $obj->serial_num;
        $bank_id = $obj->bank_id;
        $amount = $obj->amount;
        $usercard_num = $obj->usercard_num;
        $incomebankcard = $obj->incomebankcard;
        $fee = $obj->fee;
        $pay_type = $obj->pay_type;
        $processtime = isset($obj->processtime) ? $obj->processtime : date('Y-m-d H:i:s');

        $strKeyInfo = SiteSer::config('pay_gd_key');

        switch ($bank_id) {
            case "13":  //招商卡
                $comment = $obj->comment;
                $strEncypty = MD5($amount . $comment . $strKeyInfo);
                break;
            case "10":  //农行卡
            default:
                $comment = $obj->comment;
                $strEncypty = MD5($amount . $incomebankcard . $strKeyInfo);
        }
        if ($verifymd5 != $strEncypty) {
            Log::channel('charge')->info("签名错误 " . $pay_type . " " . $verifymd5 . " " . $strEncypty);
            return $this->gdResponse($obj, -3);
        }
        if (PayGD::where('serial_num', $serial_num)->exists()) {
            return $this->gdResponse($obj, -78);
        }
        //更新
        $payGD = PayGD::query()->where('charge_amount', $amount)
            ->where('comment', $comment)
            ->where('status', 0)
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-2 hours')))//只查2个小时内的
            ->orderBy('created_at', 'desc')->first();

        $time = time();
        if ($payGD && $payGD->id) {//匹配到订单
            if (strtotime($payGD->created_at) + static::NOTICE_TIMEOUT_GD * 60 >= $time) {//没超时
                $status = 2;
                $chargeResult = 2;
                $code = 88;
                $loginfo = "第三方 成功 " . $payGD->toJson();
            } else {//超时
                $status = 3;
                $chargeResult = 3;
                $code = -56;
                $loginfo = "第三方 超时 " . $payGD->toJson();
            }
            $payGD->update([
                'serial_num' => $serial_num,
                'amount' => $amount,
                'bank_id' => $bank_id,
                'pay_type' => $pay_type,
                'usercard_num' => $usercard_num,
                'fee' => $fee,
                'incomebankcard' => $incomebankcard,
                'processtime' => $processtime,
                'status' => $status,
            ]);

            $this->orderHandler($payGD->order_id, $serial_num, $loginfo, $logPath = "", $amount, $chargeResult, '',
                date('Y-m-d H:i:s', $time));
            return $this->gdResponse($obj, $code);
        }
        //未匹配到订单
        $status = 1;
        $payGD = PayGD::create([
            'serial_num' => $serial_num,
            'amount' => $amount,
            'comment' => $comment,
            'bank_id' => $bank_id,
            'del' => 0,
            'pay_type' => $pay_type,
            'usercard_num' => $usercard_num,
            'fee' => $fee,
            'incomebankcard' => $incomebankcard,
            'processtime' => $processtime,
            'status' => $status,
        ]);
        Log::channel('charge')->info('未匹配到订单' . $payGD->toJson());
        return $this->gdResponse($obj, -77);
    }

    public function gdResponse($obj, $code)
    {
        if (!is_object($obj)) {
            $obj = (object)$obj;
        }
        $delimiter = '^';
        $r = $obj->comment . $delimiter .
            $obj->bank_id . $delimiter .
            $obj->amount . $delimiter . $delimiter .
            $obj->incomebankcard . $delimiter .
            $obj->fee . $delimiter .
            $code;
        Log::channel('charge')->info('返回给古都:' . $r);
        return Response::create($code);
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
    private function orderHandler($tradeno="", $paytradeno, $loginfo, $logPath, $money, $chargeResult, $channel = '', $complateTime = '')
    {
        $loginfo = "";
        //开启事务
        try {
            DB::beginTransaction();
            $sql = 'SELECT t.* FROM `video_recharge` t WHERE t.pay_type in(1,50) AND t.pay_status < ' . Recharge::SUCCESS . ' AND order_id = "' . $tradeno . '" LIMIT 1 FOR UPDATE';
            //强制查询主库
            $stmt = DB::select($sql);

            if (empty($stmt)) {
                $dealOrCannotFind = $tradeno . "订单号：" . "\n数据已处理完毕，请查看'充值记录！'\n";
                $loginfo .= $dealOrCannotFind;
                Log::channel('charge')->info($loginfo);
                return new JsonResponse(array('status' => 0, 'msg' => $dealOrCannotFind));
            }
            $stmt = (array)$stmt[0];
            //第一步，写日志
            $loginfo .= "订单号：" . $tradeno . " 收到，并且准备更新：\n";

            $points = (int)ceil($stmt['points']);

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

            // 未完成訂單數減 1
            resolve(ChargeService::class)->decrDailyLimit($stmt['uid']);

            //刷新redis钻石
            if ($chargeStatus) {
                $userObj = DB::table('video_user')->where('uid', $stmt['uid'])->first();//Users::find($stmt['uid']);
                $loginfo .= '增加的钱数: paymoney ' . $money . ' points:' . $points . ' 最终的钱数:' . $userObj->points;
                resolve(UserService::class)->getUserReset($stmt['uid']);
            }

            // 充钱成功后 检测用户的贵族状态
            $uinfo = Users::find($stmt['uid']);
            resolve('userGroupServer')->checkUserVipStatus($uinfo);

        } catch (\Exception $e) {
            Log::channel('charge')->info("订单号：" . $tradeno . " 事务结果：" . $e->getMessage() . "\n");
            DB::rollback();
            return new JsonResponse(array('status' => 1, 'msg' => '程序内部异常'));
        }

        //首次充值时间
        if ($chargeStatus) {
            resolve('charge')->chargeAfter($stmt['uid'], $tradeno = '');
        }

        //第二步，更新数据
        $loginfo .= "订单号：" . $tradeno . " 数据处理成功！\n";

        //封装下结果给充提
        $rtn2back = $this->back2Charge($chargeResult, $tradeno, $paytradeno);
        Log::channel('charge')->info($loginfo . "返回给充提中心的结果：$rtn2back");
        return new JsonResponse(array('status' => 0, 'msg' => $rtn2back));
    }

    /**
     * 返回给充提的结果
     */
    public function back2Charge($chargeResult="", $tradeno="", $paytradeno)
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
        return $tradeno . $chargeResult2 . $paytradeno;
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

    /**
     * 通知地址
     */
    public function notice(Request $request)
    {
        $payType = $request->route('pay_type');

        if ($payType === 'onepay') {
            $data = resolve(OnePayService::class)->updateOrder(
                $request->memberid,
                $request->orderid,
                $request->merchant_order,
                $request->amount,
                $request->datetime,
                $request->returncode,
                $request->sign,
                $request->route('one_pay_token')
            );
        } else {
            //获取下数据
            $ucPostResult = file_get_contents("php://input");

            $data = resolve(ChargeService::class)->updateOrder($ucPostResult);
        }

        //记录下日志
        Log::info('传输的数据记录: ' . json_encode(
            $request->only(
                'memberid',
                'orderid',
                'merchant_order',
                'amount',
                'datetime',
                'returncode',
                'pay_ext',
                'sign'
            )
        ));

        //透過訂單號取得uid
        $uid = Recharge::where('order_id', $data['trade_no'])->value('uid');

        if ($data['status'] == 200) {
            unset($data['status']);
        } else {
            //如充值失敗,則將首充禮包icon改回顯示
            resolve(UserAttrService::class)->set($uid, 'first_gift', 0);
            $this->setStatus($data['status'], $data['msg']);
            return $this->jsonOutput();
        }

        $res = $this->orderHandler(
            $data['trade_no'],
            $data['pay_trade_no'],
            "",
            "",
            $data['money'],
            $data['charge_result'],
            $data['channel'],
            $data['complate_time']
        );

        //確認first_charge_gift_start_time是否存在
        if (!resolve(UserAttrService::class)->get($uid, 'first_charge_gift_start_time')) {
            resolve(UserAttrService::class)->set($uid, 'first_charge_gift_start_time', round(microtime(true) * 1000));
        }

        if ($payType === 'onepay') {
            return 'OK';
        }

        return $res;
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

        $payNoticeUrl = route('charge_notice');
        $postdataArr = resolve('charge')->getTestNoticeData($orderID);
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
        Log::channel('charge')->info('charge_' . $type . ' :' . $payOrderJson);
        if (!$payOrderJson) {
            return new JsonResponse(array('status' => 1, 'msg' => '传入的数据存在问题'));
        }
        $payOrderJson = json_decode($payOrderJson, true);
        if (!$this->verifyUidToken($payOrderJson['uid'], $payOrderJson['token'])) {
            return new JsonResponse(array('status' => 1, 'msg' => '非法操作！'));
        }

        $tradeno = $payOrderJson['orderId'];
        $paytradeno = $payOrderJson['payOrderId'];
        $money = $payOrderJson['amount'];
        $loginfo = json_encode($payOrderJson);
        $chargeResult = $payOrderJson['result'];
        $complateTime = $payOrderJson['complateTime'];
        $channel = '';
        return $this->orderHandler($tradeno, $paytradeno, $loginfo, $logPath = "", $money, $chargeResult, $channel, $complateTime);
    }

    public function checkKeepVip()
    {
        $msg = file_get_contents("php://input");
        Log::channel('charge')->info("checkKeepVip:" . $msg);
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
     * 充提中心查询（用于测试）
     * @param Request $request
     * @return JsonResponse
     */
    public function chongti(Request $request)
    {
        $data = $request->get('Datas')[0];
        //print_r($data);
        //return new JsonResponse((['data'=>$request->get('Datas')]));
        $recharge = Recharge::query()->where('order_id', $data['orderId'])->first();
        switch ($data['type']) {
            case 1:
                return new JsonResponse([
                    'data' => [
                        'Datas' => array(
                            array(
                                'result' => 2,
                                'payOrderId' => 'test' . mt_rand(10000, 90000),
                                'amount' => $recharge->paymoney,
                                'orderId' => $data['orderId'],
                                'complateTime' => date('Y-m-d H:i:s'),
                            )
                        )
                    ]
                ]);
                break;
            default:
                ;
        }
    }

    /**
     * 通过订单号查询
     * @return JsonResponse
     * @Author Orino
     */
    public function checkCharge(Request $request)
    {
        //修复注入漏洞
        $orderId = $request->input('orderId')??'';
        //去除％和0x攻击
        $orderId = preg_replace('/%|0x|SELECT|FROM/', ' ', $orderId);
        if (!$orderId) {
            return new JsonResponse(array('status' => 1, 'msg' => '该订单号不存在！'));
        }
        $sql = 'SELECT * FROM `video_recharge` WHERE  order_id ="' . $orderId . '"';
        //强制查询主库
        $ret = DB::select($sql);
        //$ret = DB::select('/*' . MYSQLND_MS_MASTER_SWITCH . '*/' . $sql);
        $ret = (array)$ret[0]??'';// stdClass 转数组
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

        $pay_call_url = SiteSer::config('pay_call_url');
        $local_recharge = SiteSer::config('local_recharge') ?: false;
        if (config('app.debug') && $local_recharge) {
            $pay_call_url = route('chongti');
        }
        $send_result = $this->sendCurlRequest($pay_call_url, ($POST_Array));
        $output = $send_result['data'];
        $errstr = $send_result['errstr'];

        Log::channel('charge')->info($pay_call_url . PHP_EOL . 'output' . $output . PHP_EOL . 'error' . $errstr);
        if (!empty($errstr)) {
            return new JsonResponse(array('status' => 1, 'msg' => '充提查询接口出问题：' . $errstr));
        }
        $output = json_decode($output, true);
        $output = $output['data'];
        if (!isset($output['Datas'])) {
            return new JsonResponse(array('status' => 1, 'msg' => '订单未成功支付！'));
        }
        $len = count($output['Datas']);
        $payOrderJson = [];
        for ($i = 0; $i < $len; $i++) {
            //存在同一个v项目订单号对应2个以上的财务财务订单号
            if ($output['Datas'][$i]['result'] == 2 && !empty($output['Datas'][$i]['payOrderId'])) {
                $payOrderJson = $output['Datas'][$i];
                break;
            }
        }
        //校验到成功的订单号，应该走原来通知回调的逻辑
        if (!empty($payOrderJson)) {
            $tradeno = $payOrderJson['orderId'];
            $paytradeno = $payOrderJson['payOrderId'];
            $money = $payOrderJson['amount'];
            $loginfo = json_encode($payOrderJson);
            $chargeResult = $payOrderJson['result'];
            $complateTime = $payOrderJson['complateTime'];
            $channel = '';
            return $this->orderHandler($tradeno, $paytradeno, $loginfo, $logPath = "", $money, $chargeResult, $channel, $complateTime);
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
