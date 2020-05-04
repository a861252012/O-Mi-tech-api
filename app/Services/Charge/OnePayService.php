<?php
/**
 * OnePay 金流服務
 * @author Weine
 * @date 2020-04-30
 */

namespace App\Services\Charge;


class OnePayService
{
    const ORDER_PREFIX = 'onepay_test_';

    private $orderId;

    public function __construct()
    {

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
    public function pay()
    {
        $apiUri = 'http://ptest.1-pay.co:8085/api/payment/payRequest/get';

        $result = '{"returncode": "00","bank_account": "6228480478780957872","bank_account_name": "龙浩","bank_code": "ABC","bank_area": "重庆沙坪","remark": "44QU","merchant_order": "PAY0000112020042814273200000027","alipay_bankcard_id": "1234"}';

        return $result;
    }
}