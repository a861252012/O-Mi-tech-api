<?php
/**
 * 用戶推廣 服務
 * @author Weine
 * @date 2020-03-26
 *
 */

namespace App\Services;


use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Repositories\AgentsRepository;
use App\Repositories\DomainRepository;
use App\Repositories\InstallLogRepository;
use App\Repositories\UserShareRepository;
use App\Repositories\UsersRepository;

class ShareService
{
    protected $installLogRepository;
    protected $usersRepository;
    protected $userShareRepository;
    protected $agentsRepository;


    public function __construct(
        InstallLogRepository $installLogRepository,
        UsersRepository $usersRepository,
        UserShareRepository $userShareRepository,
        AgentsRepository $agentsRepository
    ) {
        $this->installLogRepository = $installLogRepository;
        $this->usersRepository = $usersRepository;
        $this->userShareRepository = $userShareRepository;
        $this->agentsRepository = $agentsRepository;
    }

    /* 新增安裝資訊紀錄 */
    public function addInstallLog($origin, $siteId)
    {
        if (empty($origin)) {
            return false;
        }

        return $this->installLogRepository->insertLog([
            'origin'    => $origin,
            'site_id'   => $siteId,
            'sign_date' => date('Y-m-d')
        ]);
    }

    private function scodeHandler($code)
    {
        $checkChar = strtoupper(substr(md5($code), 0, 1));
        return $checkChar . $code;
    }

    /* 產生用戶分享碼 */
    public function genScode($id)
    {
        $hexId = 'U' . strtoupper(dechex($id));
        return $this->scodeHandler($hexId);
    }

    /* 產生代理分享碼 */
    public function genScodeForAgent($id)
    {
        $agentCode = 'A' . strtoupper(dechex($id));
        return $this->scodeHandler($agentCode);
    }

    /* 是否為代理 */
    public function isAgent($scode)
    {
        $shareKey = substr($scode, 1, 1);
        return 'A' === $shareKey;
    }

    /* 是否為用戶 */
    public function isUser($scode)
    {
        $shareKey = substr($scode, 1, 1);
        return 'U' === $shareKey;
    }

    /* 用戶分享解碼取得UID */
    public function decScode($scode)
    {
        if ($this->isUser($scode)) {
            return $this->getUid($scode);
        }

        if ($this->isAgent($scode)) {
            return $this->getAgentId($scode);
        }

        return false;
    }

    private function getUid($scode)
    {
        $data = substr($scode, 2);
        $uid = hexdec($data);

        $scodeCheck = $this->genScode($uid);

        /* 檢查分享碼是否有效 */
        if ($scodeCheck !== $scode) {
            return false;
        }

        /* 檢查UID是否存在 */
        if (UserSer::getUserByUid($uid)) {
            return $uid;
        }

        return false;
    }

    private function getAgentId($scode)
    {
        $data = substr($scode, 2);
        $aId = hexdec($data);

        $scodeCheck = $this->genScodeForAgent($aId);

        /* 檢查分享碼是否有效 */
        if ($scodeCheck !== $scode) {
            return false;
        }

        /* 檢查域名是否狀態 */
        if ($this->agentsRepository->getDataById($aId)) {
            return $aId;
        }

        return false;
    }


    /* 新增用戶推廣清單資訊 */
    public function addUserShare($uid, $shareUid, $aid = null, $agentName = null, $client = null, $ccMobile = null)
    {
        $now = date('Y-m-d');

        $data = [
            'reg_date'   => $now,
            'uid'        => $uid,
            'share_uid'  => $shareUid,
            'aid'        => $aid ?? '',
            'agent_name' => $agentName ?? '',
            'platform'   => in_array($client, ['android', 'ios']) ? 'mobile' : 'web',
        ];

        if (!empty($ccMobile)) {
            $data ['is_mobile_match'] = 1;
            $data ['match_date'] = $now;
        }

        return $this->userShareRepository->insertData($data);
    }

    public function modifyUserShare($uid, $data)
    {
        return $this->userShareRepository->updateDataByUid($uid, $data);
    }

    /* 随機取得域名 */
    public function randomDoamin()
    {
        $domains = collect(explode(PHP_EOL, SiteSer::siteConfig('vlanding_urls', SiteSer::siteId())));
        return $domains->random();
    }
}
