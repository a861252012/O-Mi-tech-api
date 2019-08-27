<?php

namespace App\Services\I18n;

class PhoneNumber
{
    static function checkFormat($cc, $mobile)
    {
        // see: https://github.com/giggsey/libphonenumber-for-php

        // china
        if ($cc == '86' && strlen($mobile) !== 11) {
            return false;
        }

        // taiwan
        if ($cc == '886'
            && !((strlen($mobile) == 10 && substr($mobile, 0, 2) === '09')
                || (strlen($mobile) == 9 && substr($mobile, 0, 1) === '9'))
        ) {
            return false;
        }

        return true;
    }

    static function formatMobile($cc, $mobile)
    {
        if ($cc == '886') {
            if (strlen($mobile) == 9 && substr($mobile, 0, 1) == '9') {
                $mobile = '0'. $mobile;
            }
        }
        return $mobile;
    }
}
