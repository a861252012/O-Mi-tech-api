<?php
/**
 * v串接共用 服務
 * @author Weine
 * @date 2020-11-16
 */

namespace App\Services;


use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Models\AgentsRelationship;
use App\Repositories\RechargeRepository;
use App\Repositories\UsersRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class VpubService
{

    protected $platform = [];
    protected $timeOutStr = '-1 minute';

    protected $usersRepository;
    protected $rechargeRepository;

    public function __construct(
        UsersRepository $usersRepository,
        RechargeRepository $rechargeRepository
    ) {
        $this->usersRepository = $usersRepository;
        $this->rechargeRepository = $rechargeRepository;
    }

    public function setTimeOutOfMin(int $minNum)
    {
        return "-{$minNum} minutes";
    }

    public function setTimeOutOfSec(int $secNum)
    {
        return "-{$secNum} seconds";
    }

    public function setOrigin($origin): bool
    {
        $this->platform = Redis::hgetall("hplatforms:$origin");
        return (empty($this->platform)) ? false : true;
    }

    /* 產生合作平台訂單號 */
    public function genOrderId()
    {
        $timeArr = explode(' ', microtime());
        return $this->platform['prefix'] . $timeArr[1];
    }

    /* 檢查請求ip是否在白名單內 */
    public function checkIp($ip): bool
    {
        $whiteList = explode(',', SiteSer::globalSiteConfig('vapi_wlist_' . $this->platform['origin']));
        if (in_array('*', $whiteList, true)) {
            return true;
        }

        return in_array($ip, $whiteList, true);
    }

    /* 取得api key */
    public function getApiKey()
    {
        return $this->platform['key'] ?? '';
    }

    /* 檢查簽名 */
    public function checkSignature($data, $sign, $apiKey): bool
    {
        return $this->makeSignature($data, $apiKey) === $sign;
    }

    /* 建立簽名*/
    public function makeSignature($data, $apiKey): string
    {
        ksort($data);
        return md5(implode('', $data) . $apiKey);
    }

    /* 驗證時間(現在時間前後一分鐘內) */
    public function checkTimestamp($timestamp): bool
    {
        $now = time();
        $before = strtotime($this->timeOutStr, $now);

        if ($timestamp < $before || $timestamp > $now) {
            return false;
        }

        return true;
    }

    /* 檢查訂單 */
    public function checkOrder($orderId): bool
    {
        return empty($this->rechargeRepository->orderIdExist($orderId));
    }

    /* 檢查合作平台用戶 */
    public function checkUser($userName)
    {
        $prefix = $this->platform['prefix'];
        $username = $prefix . '_' . $userName . "@platform.com";
        return UserSer::getUserByUsername($username);
    }

    /* 合作站註冊用戶 */
    public function registerUser($userName, $uuid)
    {
        $prefix = $this->platform['prefix'];
        $password = $userName . "asdfwe";

        $user = [
            'username' => $prefix . '_' . $userName . "@platform.com",
            'nickname' => $prefix . '_' . $userName,
            'sex'      => 0,
            'uuid'     => $uuid,
            'password' => $password,
            'origin'   => $this->platform['origin'],
        ];

        $uid = UserSer::register($user);
        Log::channel('plat')->info("{$this->platform['code']} 项目 注册:" . json_encode($user) . '-' . (string)$uid);

        if (!$uid) {
            Log::error('UserService註冊用戶回傳失敗');
            return false;
        }

        $agentRelationship = AgentsRelationship::create([
            'uid' => $uid,
            'aid' => $this->platform['aid'],
        ]);

        if (empty($agentRelationship)) {
            $this->usersRepository->deleteUserByUid($uid);
            Log::error('新增agent relationship失敗');
            return false;
        }

        return UserSer::getUserByUid($uid);
    }
}