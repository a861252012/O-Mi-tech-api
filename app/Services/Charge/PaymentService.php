<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2018/3/16
 * Time: 9:05
 */

namespace App\Services\Charge;


use App\Facades\SiteSer;
use App\Services\Auth\JWTGuard;
use App\Services\Service;
use Illuminate\Support\Facades\Auth;

class PaymentService extends Service
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
    private $plat_code = null;
    private $order_id = null;

    public function __construct()
    {
        parent::__construct();

        $this->remoteUrl = SiteSer::config('back_pay_call_url');
        $this->noticeUrl = SiteSer::config('back_pay_notice_url');
        $this->priviteKey = SiteSer::config('back_pay_sign_key');
        $this->plat_code = SiteSer::config('back_plat_code');

        $this->order_id = $this->generateOrderId();
    }

    public function generateOrderId()
    {
        $uid = Auth::id();
        return date('ymdHis') . mt_rand(10, 99) . sprintf('%08s', strrev($uid)) . '';
    }

    public function remote()
    {
        return $this->remoteUrl;
    }

    public function postData($amount, $cid, $remark, $origin)
    {
        $Datas = [
            'amount' => $amount,
            'cid' => $cid,
            'remark' => $remark,
            'origin' => $origin,
        ];

        $postdataArr = $this->decorateDataSign($Datas);
        $postdata = $postdataArr['data'];
        $sign = $postdataArr['sign'];
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        return collect([base64_encode(json_encode($postdata)), $sign])->implode('.');
    }

    public function decorateDataSign(array $Datas): array
    {
        $temp['data'] = $this->decorateData($Datas);

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
        $time = time();
        $timeHex = dechex($time);
        $notice = $this->noticeUrl;
        $origin = $Datas['origin'];
        $isMobile = $this->checkMobile() ? "true" : "false";
        return [
            'order_id' => $this->generateOrderId(),
            'money' => $Datas['amount'],
            'channel' => $Datas['cid'],
            'notice' => $notice,
            'isMobile' => $isMobile,
            'origin' => $origin,
            'plat_code' => $this->plat_code,
            'uid' => Auth::id(),
            'user_name' => Auth::user()['username'],
            'remark' => $Datas['remark'],
            't' => $timeHex
        ];
    }

    //支付数据  postData

    public function checkMobile()
    {
        return config()->get('auth.defaults.guard') == JWTGuard::guard;
    }

    /**
     * 充提生成sign
     * @param $postResult
     * @return string
     */
    public function sign($postResult)
    {
        $key = $this->priviteKey;
        return hash_hmac('sha256', json_encode($postResult), $key);
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

    public function nickname()
    {
        return Auth::user()['username'];
    }

    public function getMessageNo()
    {
        return $this->messageNo;
    }

    public function getTestNoticeData($orderID, $amount)
    {
        $jsondatas['Datas']['0']['dataNo'] = $this->getDataNo();
        $jsondatas['Datas']['0']['amount'] = $amount;
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
    public function getDataNo()
    {
        return 'FCDATA' . $this->generateOrderId();
    }

    public function noticeUrl()
    {
        return $this->noticeUrl;
    }

    public function key()
    {
        return $this->priviteKey;
    }

    public function checkSign($postResult)
    {
        //传过来的sign
        $oldSign = $postResult['sign'];
        return $this->sign($postResult) == $oldSign;
    }

}