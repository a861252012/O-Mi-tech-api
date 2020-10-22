<?php
/**
 * PayPal 金流服務
 */

namespace App\Services\Charge;

use App\Facades\SiteSer;

class PayPalService
{
    const ORDER_PREFIX = 'PPL';

    /* PayPal設定 */
    private $payPalAccount;

    public function __construct()
    {
        $this->payPalAccount = json_decode(SiteSer::globalSiteConfig('paypal_account'));
    }

    /* 產生訂單id */
    public function genOrderId(): string
    {
        return sprintf(
            "%s%s%s",
            self::ORDER_PREFIX,
            date('YmdHis'),
            substr(str_shuffle(str_repeat('0123456789', 5)), 0, 8)
        );
    }

    /* 取得PayPal帳戶資訊 */
    public function getAccount()
    {
        return json_encode($this->payPalAccount);
    }
}