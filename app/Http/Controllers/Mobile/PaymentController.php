<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2017/1/18
 * Time: 11:17
 */

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\ChargeController;
use App\Libraries\ErrorResponse;
use App\Libraries\SuccessResponse;
use App\Models\GiftActivity;
use App\Models\PayAccount;
use App\Models\PayGD;
use App\Models\Recharge;
use App\Services\User\UserService;
use Hashids\Hashids;
use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $origin = $request->get('origin', 12);
        $token = Auth::getToken();
//        if ($open) {
//            $var['options'] = PayOptions::with('channels')->where('device', 'MOBILE')->orderBy('sid','desc')->get();
//            $var['payConfig'] = PayConfig::where('open', 1)->get(['id', 'cid', 'bus', 'channel']);
//            $var['origin'] = $origin;
//            $var['jwt'] = $token;
//            //右边广告图
//            $var['ad'] = '';
//            $ad = $this->make('redis')->hget('img_cache', 3);// 获取右边的广告栏的数据
//            if ($ad) {
//                $a = json_decode($ad, true);
//                $var['ad'] = $a[0];
//            }
//            $var['pay'] = 2;
//            return SuccessResponse::create($var);
//        }

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
    public function pay(Request $request)
    {
        $amount = isset($_POST['price']) ? number_format(intval($_POST['price']), 2, '.', '') : 0;
        $origin = $this->request()->get('origin') ?: 21;
        if (!$amount || $amount < 1) {
            $msg = '请输入正确的金额!';
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }
        //获取下渠道
        $channel = $_POST['vipLevel'];
        $mode_type = $_POST['mode_type'];
        //ARD IOS
        $plat = isset($_POST['plat']) ? $_POST['plat'] : "";
        //判断下渠道存不存在
        if (empty($channel)) {
            $msg = '请选择充值渠道!';
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }

        $fee = 0;
        if ($giftactive = GiftActivity::query()->where('moneymin', intval($amount))->first()) $fee = $giftactive->fee;
        /** 古都 */
        if (intval($mode_type) === ChargeController::CHANNEL_GD_ALI || intval($mode_type) === ChargeController::CHANNEL_GD_BANK) {
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
        $uid = Auth::id();//todo recheck session
        $client = $request->headers->get('client');
        switch ($client) {
            case "1001":
                $origin = 22;
                break;
            case "1002":
                $origin = 32;
                break;
            default:
                $origin = 22;
        }
        Recharge::create(
            array(
                'uid' => $uid,
                'created' => date('Y-m-d H:i:s'),
                'pay_status' => 0,// 刚开始受理
                'pay_type' => Recharge::PAY_TYPE_CHONGTI, // 银行充值
                'del' => 0,
                'paymoney' => $amount,
                'points' => ceil($amount * 10),
                'order_id' => resolve('charge')->getMessageNo(),
                'postdata' => $postdata,
                'nickname' => Auth::user()['username'],
                'channel' => $channel,
                'mode_type' => $mode_type,
                'origin' => $origin
            )
        );

        $rtn = array(
            'postdata' => $postdata,
            'remoteUrl' => resolve('charge')->remote(),
        );
        return new JsonResponse(array('status' => 0, 'data' => $rtn));
    }

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
        if ($modeType == ChargeController::CHANNEL_GD_ALI && empty($comment)) {
            //支付宝转账需要输入名字
            return JsonResponse::create(['status' => 0, 'msg' => '请输入名称']);
        } elseif ($modeType == ChargeController::CHANNEL_GD_BANK) {
            //银行转账生成唯一备注
            $comment = (new Hashids($uid, 4, 'abcdefghijklmnopqrstuvwxyz1234567890'))
                ->encode(mt_rand(1, 1000000));
        }
        $obj = PayGD::query()->whereNotIn('status', [2, 4])->where('charge_amount', $money)->where('comment', $comment)->orderBy('id', 'desc')->first();
        if ($obj && strtotime($obj['created_at']) + ChargeController::ORDER_REPEAT_LIMIT_GD * 60 > time()) {
            $err = "1小时内，不能提同一金额，同一姓名的订单";
            return JsonResponse::create(['status' => 0, 'msg' => $err]);
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
            ]
        );

        PayGD::create([
            'charge_id' => $recharge->id,
            'order_id' => $order_id,
            'comment' => $comment,
            'charge_amount' => $money,
            'uid' => $uid,
        ]);

        return JsonResponse::create(['data' => $postdata]);

    }

    public function generateOrderId()
    {
        return date('ymdHis') . mt_rand(10, 99) . sprintf('%08s', strrev(Auth::id())) . '';
    }
}