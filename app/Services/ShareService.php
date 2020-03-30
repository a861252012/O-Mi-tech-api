<?php
/**
 * 用戶推廣 服務
 * @author Weine
 * @date 2020-03-26
 *
 */

namespace App\Services;


use App\Facades\SiteSer;
use App\Repositories\InstallLogRepository;
use App\Repositories\UsersRepository;

class ShareService
{
    protected $installLogRepository;
    protected $usersRepository;


    public function __construct(InstallLogRepository $installLogRepository, UsersRepository $usersRepository)
    {
        $this->installLogRepository = $installLogRepository;
        $this->usersRepository = $usersRepository;
    }

    /* 新增安裝資訊紀錄 */
    public function addInstallLog($origin, $siteId)
    {
        return $this->installLogRepository->insertLog([
            'origin'    => $origin,
            'site_id'   => $siteId,
            'sign_date' => date('Y-m-d')
        ]);
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
        $user = $this->usersRepository->getUserById($uid);
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