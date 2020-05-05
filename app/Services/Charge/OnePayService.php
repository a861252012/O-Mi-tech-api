<?php
/**
 * OnePay 金流服務
 * @author Weine
 * @date 2020-04-30
 */

namespace App\Services\Charge;


use GuzzleHttp\Client;

class OnePayService
{
    const SECRET_KEY = 'VdNIVdkVP1WnmtfRiMhZ41OuA7WHkmIMJ1wAlqQB2102NNRNT2W2K6nL6m58';
    const ORDER_PREFIX = 'onepay_test_';
    const MEMBER_ID = 'XO01';

    private $orderId;

    public function __construct()
    {

    }

    /* 建立簽名 */
    private function genSign($payload)
    {
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

        $oStr = collect(collect($payload)->only($encStrKey))->map(function ($item, $key) {
            return trim($item);
        })->implode('');

        return md5($oStr . self::SECRET_KEY);
    }

    /* 產生訂單id */
    public function genOrder() : bool
    {
        $this->orderId = self::ORDER_PREFIX . substr(str_shuffle(str_repeat('0123456789', 5)), 0, 5);

        return empty($this->orderId) ? false : true;
    }

    /* 取得訂單id */
    public function getOrderId() : string
    {
        return $this->orderId;
    }

    /* 充值接口 – 取得銀行卡資訊 */
    public function pay($price)
    {
        $apiUri = 'http://ptest.1-pay.co:8085/api/payment/payRequest/get';

        if (empty($this->orderId)) {
            $this->genOrder();
        }

        $payload = [
            'pay_amount'      => $price,
            'pay_applydate'   => date('Y-m-d H:i:s', strtotime('+8 hours')),
            'pay_productname' => '充值',
            'pay_bankcode'    => 'cardPay',
            'pay_callbackurl' => 'http://ptest.1-pay.co:8085/pay/result',
            'pay_memberid'    => self::MEMBER_ID,
            'pay_notifyurl'   => 'http://ptest.1-pay.co:8085/pay/notify',
            'pay_orderid'     => $this->orderId,
        ];

        $payload['sign'] = $this->genSign($payload);
        info('One Pay充值payload: ' . var_export($payload, true));

//        $result = '{"returncode": "00","bank_account": "6228480478780957872","bank_account_name": "龙浩","bank_code": "ABC","bank_area": "重庆沙坪","remark": "44QU","merchant_order": "PAY0000112020042814273200000027","alipay_bankcard_id": "1234"}';

        $client = new Client();
        $result = $client->request('POST', $apiUri, [
            'form_params' => $payload,
        ]);

//        dd($result->getStatusCode(), $result->getBody()->getContents());

        if ($result->getStatusCode() != 200) {
            \Log::error('金流回應HTTP status: ' . $result->getStatusCode());
            return false;
        }

        $onePayCollection = collect(json_decode($result->getBody()->getContents()));

        if ($onePayCollection->get('returncode') != "00") {
            \Log::error("One Pay支付錯誤代碼: " . ($onePayCollection->get('returncode') ?? '999'));
            return false;
        }

        $onePayCollection->put('price', $price);

        info('One Pay支付回應: ' . var_export($onePayCollection->toJson(), true));

//        echo $onePayCollection->toJson();exit;

        return $onePayCollection->toJson();
    }

    /* 異步通知 */
    public function notify($data)
    {
        return true;
    }
}