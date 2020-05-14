<?php
/**
 * OnePay 金流服務
 * @author Weine
 * @date 2020-04-30
 */

namespace App\Services\Charge;


use App\Constants\BankCode;
use App\Facades\SiteSer;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OnePayService
{
    const TOKEN_CODE = 'EEE0xIJXaT';

    const ORDER_PREFIX = 'OP';

    /* One Pay設定 */
    private $onePaySettings;

    private $orderId;

    private $apiHost;

    /* 金流回應碼 */
    private $status = '';

    public function __construct()
    {
        $this->apiHost = SiteSer::config('api_host');
        $this->onePaySettings = json_decode(SiteSer::globalSiteConfig('onepay_setting'));
    }

    /* 建立簽名 */
    private function genSign($payload, $encStrKey)
    {
        $oStr = collect(collect($payload)->only($encStrKey))->map(function ($item, $key) {
            return trim($item);
        })->implode('');

        return md5($oStr . $this->onePaySettings->secret_key);
    }

    /* 產生token */
    public function genToken($orderId)
    {
        if (empty($orderId)) {
            return false;
        }

        return md5($orderId . self::TOKEN_CODE);
    }

    /* 檢查token */
    public function checkToken($orderId, $token)
    {
        return ($this->genToken($orderId) === $token) ? true : false;
    }

    /* 產生訂單id */
    public function genOrder()
    {
        $this->orderId = self::ORDER_PREFIX . date('YmdHis') . substr(str_shuffle(str_repeat('0123456789', 5)), 0, 8);
    }

    /* 取得訂單id */
    public function getOrderId() : string
    {
        return $this->orderId;
    }

    /* 取得金流回應碼 */
    public function getStatus()
    {
        return $this->status;
    }

    /* 充值接口 – 取得銀行卡資訊 */
    public function pay($price)
    {
        $apiUri = $this->onePaySettings->host . $this->onePaySettings->pay_api;

        if (empty($this->orderId)) {
            $this->genOrder();
        }

        $payload = [
            'pay_amount'      => $price,
            'pay_applydate'   => date('Y-m-d H:i:s', strtotime('+8 hours')),
            'pay_productname' => '充值',
            'pay_bankcode'    => 'cardPay',
            'pay_callbackurl' => $this->apiHost . '/api/charge/notice/one_pay/' . $this->genToken($this->orderId),
            'pay_memberid'    => $this->onePaySettings->member_id,
            'pay_notifyurl'   => $this->apiHost . '/api/charge/notice/one_pay/' . $this->genToken($this->orderId),
            'pay_orderid'     => $this->orderId,
        ];

        /* 簽名所需的key序 */
        $encStrKey = [
            'pay_amount',
            'pay_applydate',
            'pay_productname',
            'pay_bankcode',
            'pay_callbackurl',
            'pay_memberid',
            'pay_notifyurl',
            'pay_orderid'
        ];

        $payload['sign'] = $this->genSign($payload, $encStrKey);
        Log::debug('One Pay充值payload: ' . var_export($payload, true));

//        $result = '{"returncode": "00","bank_account": "6228480478780957872","bank_account_name": "龙浩","bank_code": "ABC","bank_area": "重庆沙坪","remark": "44QU","merchant_order": "PAY0000112020042814273200000027","alipay_bankcard_id": "1234"}';

        $client = new Client();
        $result = $client->request('POST', $apiUri, [
            'form_params' => $payload,
        ]);

        if ($result->getStatusCode() != 200) {
            Log::debug('金流回應HTTP status: ' . $result->getStatusCode());
            $this->status = '01';
            return false;
        }

        $onePayCollection = collect(json_decode($result->getBody()->getContents()));

        if ($onePayCollection->get('returncode') != "00") {
            Log::debug("One Pay支付錯誤代碼: " . ($onePayCollection->get('returncode') ?? '999'));
            $this->status = $onePayCollection->get('returncode');
            return false;
        }

        $onePayCollection->put('price', $price);
        $onePayCollection->put('bank_name', BankCode::CN[$onePayCollection->get('bank_code')]);

        info('One Pay支付回應: ' . var_export($onePayCollection->toJson(), true));

        return $onePayCollection->toJson();
    }

    /* 異步通知 */
    public function updateOrder($tradeNo, $payTradeNo, $money, $complateTime, $chargeResult, $token)
    {
        if (!$this->checkToken($tradeNo, $token)) {
            $res['status'] = 999;
            $res['msg'] = 'Token Wrong !';
            return $res;
        }

        if ($chargeResult != 00) {
            $chargeResult = 3;
        } else {
            $chargeResult = 2;
        }

        $res['status'] = 200;
        $res['trade_no'] = $tradeNo;
        $res['pay_trade_no'] = $payTradeNo;
        $res['money'] = $money;
        $res['complate_time'] = $complateTime;
        $res['charge_result'] = $chargeResult;

        return $res;
    }
}