<?php
/**
 * 登入 服務
 * @author Weine
 * @date 2020-05-06
 */

namespace App\Services;


use App\Facades\SiteSer;
use Illuminate\Support\Facades\Hash;

class LoginService
{
    /* 自動化驗證碼檢查 */
    public function autoCheck($captcha)
    {
//        $captchaCode = SiteSer::globalSiteConfig('auto_captcha');
        /* 本以不分站點設定存放，討論後先以hardcode方式 */
        $autoCaptcha = 'e10adc3949ba59abbe56e057f20f883e';

        if ($captcha == $autoCaptcha) {
            return true;
        }

        return false;
    }
}