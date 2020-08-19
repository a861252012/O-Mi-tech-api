<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2018/3/16
 * Time: 9:05
 */

namespace App\Services\Charge;

use App\Constants\LvRich;
use App\Entities\RechargeBlockIp;
use App\Entities\UserAttr;
use App\Models\Recharge;
use App\Models\Users;
use App\Models\RechargeWhiteList;
use App\Services\Auth\JWTGuard;
use App\Services\FirstChargeService;
use App\Services\Service;
use App\Services\Site\SiteService;
use App\Services\User\UserService;
use Illuminate\Redis\RedisManager;
use App\Services\UserAttrService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ChargeService extends Service
{
    private $serviceCode = null;
    private $remoteUrl = null;
    private $messageNo = null;
    private $version = null;
    private $serviceType = null;
    private $signType = null;
    private $sysPlatCode = null;
    private $charset = null;
    private $priviteKey = null;
    private $noticeUrl = null;
    private $returnUrl = null;
    private $randValue = null;
    private $dataNo = null;
    private $userAttrService;
    private $userService;

    const ORDER_DAILY_CNT_PREFIX = 'ordercnt:';
    const ORDER_DAILY_LIMIT = 5;    // 每日五次

    public function __construct()
    {
        parent::__construct();

        //渠道转化为数字入库
        $this->serviceCode = resolve(SiteService::class)->config('pay_service_code');
        $this->version = resolve(SiteService::class)->config('pay_version');
        $this->serviceType = resolve(SiteService::class)->config('pay_service_type');
        $this->signType = resolve(SiteService::class)->config('pay_signtype');
        $this->sysPlatCode = resolve(SiteService::class)->config('pay_sysplatcode');
        $this->charset = resolve(SiteService::class)->config('pay_charset');
        $this->priviteKey = resolve(SiteService::class)->config('pay_privatekey');
        $this->remoteUrl = resolve(SiteService::class)->config('pay_call_url');
        $this->noticeUrl = resolve(SiteService::class)->config('pay_notice_url');
        $this->returnUrl = resolve(SiteService::class)->config('pay_reback_url');
        $this->randValue = $this->generateOrderId();
        ////随意给的，只是让校验产生随机性质
        $this->messageNo = $this->serviceCode . $this->randValue;
        $this->dataNo = $this->getDataNo();
        $this->userAttrService = resolve(UserAttrService::class);
        $this->userService = resolve(UserService::class);
    }

    public function generateOrderId()
    {
        $uid = Auth::id();
        //return sprintf('%08s', strrev($uid)) . date('ymdHis') . mt_rand(10, 99) . '';
        return sprintf('%08s', strrev($uid)) . microtime(true) * 10000;
    }

    public function getDataNo()
    {
        return 'FCDATA' . $this->generateOrderId();
    }

    public function remote()
    {
        return $this->remoteUrl;
    }

    public function postData($amount, $channel)
    {
        $Datas = $this->getDatas($amount, $channel);

        $postdataArr = $this->decorateDataSign($Datas);
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        return json_encode($postdataArr);
    }

    public function getDatas($amount, $channel): array
    {
        //通知地址
        $username = $this->nickname();
        $isMobile = $this->checkMobile() ? "true" : "false";
        return array(
            array(
                'dataNo' => $this->dataNo,
                'amount' => $amount,
                'noticeUrl' => $this->noticeUrl,
                'returnUrl' => $this->returnUrl,
                'remark' => $username,
                'channel' => "",
                'vipLevel' => $channel,
                'bankCode' => "",
                'lan' => "",
                'currency' => "",
                'isMobile' => $isMobile,
            )
        );
    }

    public function nickname()
    {
        return Auth::user()['username'];
    }

    public function checkMobile()
    {
        return config()->get('auth.defaults.guard') == JWTGuard::guard;
    }

    //支付数据  postData

    public function decorateDataSign(array $Datas): array
    {
        $temp = $this->decorateData($Datas);
        $temp['sign'] = $this->sign($temp);
        return $temp;
    }

    /**
     * 封装成充提需要的数据格式
     * @param $Datas
     * @return array
     */
    private function decorateData($Datas): array
    {
        return array(
            'serviceCode' => $this->serviceCode,
            'version' => $this->version,
            'serviceType' => $this->serviceType,
            'signType' => $this->signType,
            'sysPlatCode' => $this->sysPlatCode,
            'sentTime' => date('Y-m-d H:i:s'),
            'expTime' => '',
            'charset' => $this->charset,
            'sMessageNo' => $this->getMessageNo(),
            'Datas' => $Datas
        );
    }

    public function getMessageNo()
    {
        return $this->messageNo;
    }

    /**
     * 充提生成sign
     * @param $postResult
     * @return string
     */
    public function sign($postResult)
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
        $priviteKey = $this->priviteKey;
        //生成签名
        $newDatas = $Datas[0];
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        $str = $serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset . $sMessageNo;
        foreach ($newDatas as $value) {
            $str .= $value;
        }
        $str .= $priviteKey;
        $str = trim($str);

        return MD5((string)$str);
    }

    public function getFindRequest($orderId = ""): array
    {
        $Datas = array(
            array(
                "dataNo" => $orderId,
                "orderId" => $orderId,
                "payOrderId" => "",
                "type" => 1 //查询接口类型
            )
        );
        return $this->decorateDataSign($Datas);
    }

    public function getTestNoticeData($orderID)
    {
        $jsondatas['Datas']['0']['dataNo'] = $this->getDataNo();
        $recharge = Recharge::query()->where('order_id', $orderID)->first();
        $jsondatas['Datas']['0']['amount'] = number_format($recharge->points / 10, 2);
        $jsondatas['sMessageNo'] = $orderID;
        echo "$orderID $amount 转化成充提中心给我的json格式\n";
        $datas = array(
            array(
                'dataNo' => $jsondatas['Datas']['0']['dataNo'],
                'orderId' => $jsondatas['sMessageNo'],
                'payOrderId' => mt_rand(10000000, 99999999),
                'amount' => $jsondatas['Datas']['0']['amount'],
                'result' => 2,
                'remark' => '',
                'channel' => 'NP',
                'complateTime' => date('Y-m-d H:i:s'),
            )
        );
        return $this->decorateDataSign($datas);
    }

    public function noticeUrl()
    {
        return $this->noticeUrl;
    }

    public function key()
    {
        return $this->priviteKey;
    }

    public function chargeAfter($uid): void
    {
        $userRich = (int)$this->userService->getUserInfo($uid, 'rich');
        $newUserRichLv = LvRich::calcul($userRich + 500);

        $data = [
            'first_charge_time' => date('Y-m-d H:i:s'),
            'rich'              => $userRich + 500,
            'lv_rich'           => $newUserRichLv
        ];

        $rs = Users::query()->whereRaw('uid=' . $uid . '  and first_charge_time is NULL')->update($data);
        $rs && resolve(UserService::class)->cacheUserInfo($uid, $data);
    }

    public function checkSign($postResult)
    {
        //传过来的sign
        $oldSign = $postResult['sign'];
        return $this->sign($postResult) == $oldSign;
    }

    public function updateOrder($postResult)
    {
        //拿到通知的数据
        if (!$postResult) {
            return ['status' => 1, 'msg' => 'no data input!'];
        }

        $jsondatas = json_decode($postResult, true);
        $len = $jsondatas['Datas'] ? count($jsondatas['Datas']) : 0;
        if (json_last_error() > 0 || $len == 0) {
            return ['status' => 1, 'msg' => 'json string ie error!'];
        }

        $tradeno = $jsondatas['Datas'][0]['orderId'];//拿出1个账单号

        //验证下签名
        if (!resolve('charge')->checkSign($jsondatas)) {
            $signError = __('messages.Charge.notice.sign_wrong', ['tradeno' => $tradeno]);

            Log::info($signError);
            return ['status' => 1, 'msg' => date('Y-m-d H:i:s') . " \n" . $postResult . "\n" . $signError];
        }

        $money = $chargeResult = $channel = $complateTime = null;

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

        $res['status'] = 200;
        $res['trade_no'] = $tradeno;
        $res['pay_trade_no'] = $paytradeno;
        $res['money'] = $money;
        $res['channel'] = $channel;
        $res['complate_time'] = $complateTime;
        $res['charge_result'] = $chargeResult;

        return $res;
    }

    // 檢查是否達到暫存的每日上限
    // 這邊不能直接檢查 DB 的原因是希望後台可以解除黑名單後，用戶馬上可用
    // 所以只用 Redis 紀錄次數
    public function isDailyLimitReached($uid)
    {
        $key = $this->getDailyLimitCntKey($uid);
        $cnt = Redis::get($key);
        if ($cnt < self::ORDER_DAILY_LIMIT) {
            return false;
        }

        // 達上限，寫入黑名單
        RechargeWhiteList::create([
            'uid'    => $uid,
            'author' => 1,      // admin
            'type'   => 1,      // 黑名單
        ]);

        // 刪 key
        Redis::del($key);

        // 把最近 20 筆訂單的 IP 加到 IP 黑名單庫
        $enable_block = resolve(SiteService::class)->config('enable_recharge_block_ip') == "1";
        if ($enable_block) {
            $ips = Recharge::query()
                ->where('uid', $uid)
                ->where('ip', '<>', '')
                ->orderby('created', 'DESC')
                ->limit(20)
                ->distinct()
                ->pluck('ip')
                ->toArray();

            foreach ($ips as $ip) {
                RechargeBlockIp::updateOrCreate(['ip' => $ip], [
                    'modified' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 寫 log
        $loginfo = "UID: {$uid} 未處理訂單數量達 {$cnt} 次，加入黑名單";
        Log::channel('charge')->info($loginfo);

        return true;
    }

    private function getDailyLimitCntKey($uid)
    {
        return self::ORDER_DAILY_CNT_PREFIX . $uid .':'. date('Ymd');
    }

    public function incrDailyLimit($uid)
    {
        $key = $this->getDailyLimitCntKey($uid);
        $cnt = Redis::incr($key);
        if ($cnt == 1) {
            Redis::expire($key, (24 - date('G')) * 3600);
        }
        return $cnt;
    }

    public function decrDailyLimit($uid)
    {
        $key = $this->getDailyLimitCntKey($uid);
        $cnt = Redis::decr($key);
        if ($cnt <= 0) {
            Redis::del($key);
            $cnt = 0;
        }
        return $cnt;
    }

    public function isIpBlocked($ip)
    {
        $blocked = RechargeBlockIp::where('ip', $ip)->first();
        return !is_null($blocked);
    }
}
