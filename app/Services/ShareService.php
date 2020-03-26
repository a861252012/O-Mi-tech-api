<?php
/**
 * 用戶推廣 服務
 * @author Weine
 * @date 2020-03-26
 *
 */

namespace App\Services;


use App\Facades\SiteSer;
use App\Repositories\UsersRepository;

class ShareService
{
    protected $usersRepository;

    public function __construct(UsersRepository $usersRepository)
    {
        $this->usersRepository = $usersRepository;
    }

    /* 產生分享碼 */
    public function genScode($id)
    {
        $hexId = 'U' . strtoupper(dechex($id));
        $checkChar = strtoupper(substr(md5($hexId), 0, 1));
        $scode = $checkChar . $hexId;
        return $scode;
    }

    /* 分享解碼取得UID */
    public function decScode($scode)
    {
        $data = explode('U', $scode);
        $uid = hexdec($data[1]);

        /* 檢查UID是否存在 */
        $user = $this->usersRepository->getUserByUid($uid);
        if (empty($user)) {
            return false;
        }

        $scodeCheck = $this->genScode($uid);

        /* 檢查分享碼是否有效 */
        if ($scodeCheck === $scode) {
            return $uid;
        }

        return false;
    }

    /* 随機取得域名 */
    public function randomDoamin()
    {
        $domains = collect(explode(PHP_EOL, SiteSer::siteConfig('vdomain_list', SiteSer::siteId())));
        return $domains->random();
    }
}