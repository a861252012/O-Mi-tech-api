<?php
/**
 * OnePay 金流服務
 * @author Weine
 * @date 2020-04-30
 */

namespace App\Services\Charge;


class OnePayService
{
    public function __construct()
    {

    }

    /* 充值接口 – 取得銀行卡資訊 */
    public function pay()
    {
        $result = '{
                      "returncode": "00",
                      "bank_account": "6228480478780957872",
                      "bank_account_name": "龙浩",
                      "bank_code": "ABC",
                      "bank_area": "重庆沙坪",
                      "remark": "44QU",
                      "merchant_order": "PAY0000112020042814273200000027",
                      "alipay_bankcard_id": "1234"
                    }';

        return $result;
    }
}