<?php
/**
 * 用戶推廣 服務
 * @author Weine
 * @date 2020-03-26
 *
 */

namespace App\Services;


use App\Facades\SiteSer;

class ShareService
{
    /* 產生分享碼 */
    public function genScode($id)
    {
        $hexId = 'U' . strtoupper(dechex($id));
        $checkChar = strtoupper(substr(md5($hexId), 0, 1));
        $scode = $checkChar . $hexId;
        return $scode;
    }

    /**/
    public function randomDoamin()
    {
        $domains = collect(explode(PHP_EOL, SiteSer::siteConfig('vdomain_list', SiteSer::siteId())));
        return $domains->random();
    }
}