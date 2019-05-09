<?php /** @noinspection PhpUndefinedClassInspection */

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Libraries\ErrorResponse;
use App\Libraries\SuccessResponse;
use App\Models\GiftActivity;
use App\Models\PayAccount;
use App\Models\PayGD;
use App\Models\Recharge;
use App\Models\Users;
use App\Services\User\UserService;
use DB;
use Hashids\Hashids;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ChargeController extends Controller
{
    const NOTICE_TIMEOUT_GD = 30;
    const CHANNEL_GD_ALI = 7;
    const CHANNEL_GD_BANK = 8;
    const ORDER_REPEAT_LIMIT_GD = 60;

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
            return ErrorResponse::create(array('title' => '尊敬的用户，您好，恭喜您成为今日幸运之星，请点击在线客服领取钻石，感谢您的支持与理解！', 'msg' => ''));
        }

        if (resolve('chargeGroup')->customer($uid)) {
            return ErrorResponse::create(array('title' => '需要充值请联系客服！！！', 'msg' => ''));
        }

        $var['active'] = GiftActivity::where('type', 2)->get();

        //充值方式数组
        $var['recharge_type'] = resolve('chargeGroup')->channel($uid);

        //检查用户登录权限
        $var['user_login_asset'] = true;

        //最小充值限制
        $var['user_money_min'] = config('const.user_points_min') / 10;

        //充值金额删选数组
        $recharge_money = $this->make('redis')->get('recharge_money') ? json_decode($this->make('redis')->get('recharge_money')) : [];
        $temp = [];
        foreach ($recharge_money as $k => $value) {
            if (isset($value->client) && $value->client == $client) {
                array_push($temp, $value);
            }
        }
        $var['recharge_money'] = json_encode($temp);
        $var['token'] = $token;
        $var['pay'] = 1;
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
     */
    public function pay()
    {
        $amount = isset($_POST['price']) ? number_format(intval($_POST['price']), 2, '.', '') : 0;

        if (!$amount || $amount < 1) {
            $msg = '请输入正确的金额!';
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }
        $fee = 0;
        if ($giftactive = GiftActivity::query()->where('moneymin', intval($amount))->first()) {
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

        $origin = $this->getClient();
        /** 古都 */
        if (intval($mode_type) === static::CHANNEL_GD_ALI || intval($mode_type) === static::CHANNEL_GD_BANK) {
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
        $postdata = resolve('charge')->postData($amount, $channel);

        //记录下数据库
        $uid = Auth::id();
        Recharge::create(
            array(
                'uid' => $uid,
                'created' => date('Y-m-d H:i:s'),
                'pay_status' => 0,// 刚开始受理
                'pay_type' => Recharge::PAY_TYPE_CHONGTI, // 银行充值
                'del' => 0,
                'paymoney' => $amount,
                'points' => ceil($amount * 10) + $fee,
                'order_id' => resolve('charge')->getMessageNo(),
                'postdata' => $postdata,
                'nickname' => Auth::user()['nickname'],
                'channel' => $channel,
                'mode_type' => $mode_type,
                'origin' => $origin
            )
        );

        $rtn = array(
            'postdata' => $postdata,
            'orderId' => resolve('charge')->getMessageNo(),
            'remoteUrl' => resolve('charge')->remote(),
        );

        Log::channel('charge')->info($rtn);
        return new JsonResponse(array('status' => 0, 'data' => $rtn));
    }

    public function exchange(Request $request)
    {
        //修复注入漏洞
        $status = $request->input('status')??'';
        $orderid = $request->input('orderid')??'';
        //去除％和0x攻击
        $orderid = preg_replace('/%|0x|SELECT|FROM/', ' ', $orderid);
        if (!$orderid) {
            return new JsonResponse(array('status' => 1, 'msg' => '没有订单号！'));
        }

        //强制查询主库
        $ret = Recharge::where('order_id', $orderid)->where('pay_status', 4)->first();

        if (!$ret) {
            return new JsonResponse(array('status' => 1, 'msg' => '该订单号不存在！'));
        }
        if(empty($status)){
            return new JsonResponse(array('status' => 1, 'msg' => '状态不正确！'));
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
            $this->make('redis')->hincrby('huser_info:' . $ret->uid, 'points', $ret->points);

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

            //刷新redis钻石
            if ($chargeStatus) {
                $userObj = DB::table('video_user')->where('uid', $stmt['uid'])->first();//Users::find($stmt['uid']);
                $loginfo .= '增加的钱数: paymoney ' . $money . ' points:' . $points . ' 最终的钱数:' . $userObj->points;
                $this->make('redis')->hincrby('huser_info:' . $stmt['uid'], 'points', $points);
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
            resolve('charge')->chargeAfter($stmt['uid']);
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
            return new JsonResponse(array('status' => 1, 'msg' => date('Y-m-d H:i:s') . " \n" . $postResult . "\n" . $signError));
        }
        $money=$chargeResult=$channel=$complateTime=null;
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
        return $this->orderHandler($tradeno, $paytradeno, $loginfo = "", $logPath = "", $money, $chargeResult, $channel, $complateTime);
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
