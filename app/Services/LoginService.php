<?php
/**
 * 登入 服務
 * @author Weine
 * @date 2020-05-06
 */

namespace App\Services;


class LoginService
{
    /* 自動化驗證碼檢查 */
    public function autoCheck($captcha)
    {
        if ($captcha == '123456') {
            return true;
        }

        return false;
    }
}