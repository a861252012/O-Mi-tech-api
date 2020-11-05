<?php
/**
 * PayPal 金流服務
 */

namespace App\Services\Charge;

use App\Facades\SiteSer;

class PayPalService
{
    const ORDER_PREFIX = 'PPL';

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
        return SiteSer::globalSiteConfig('paypal_account');
    }
}