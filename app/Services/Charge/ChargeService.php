<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2018/3/16
 * Time: 9:05
 */

namespace App\Services\Charge;


use App\Models\Recharge;
use App\Models\Users;
use App\Services\Auth\JWTGuard;
use App\Services\Service;
use App\Services\Site\SiteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $this->messageNo = $this->serviceCode . $this->randValue;;
        $this->dataNo = $this->getDataNo();
    }

    public function generateOrderId()
    {
        $uid = Auth::id();
        //return sprintf('%08s', strrev($uid)) . date('ymdHis') . mt_rand(10, 99) . '';
        return sprintf('%08s', strrev($uid)) . microtime(true)*10000;
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
        //Users::query()->where('uid', $uid)->whereNull('first_charge_time')->update(array('first_charge_time' => date('Y-m-d H:i:s')));

        Users::query()->whereRaw('uid='.$uid.'  and first_charge_time is NULL')->update(array('first_charge_time' => date('Y-m-d H:i:s')));
        $this->make('redis')->hset('huser_info:' . $uid, 'first_charge_time', date('Y-m-d H:i:s', time()));
    }

    public function checkSign($postResult)
    {
        //传过来的sign
        $oldSign = $postResult['sign'];
        return $this->sign($postResult) == $oldSign;
    }

}