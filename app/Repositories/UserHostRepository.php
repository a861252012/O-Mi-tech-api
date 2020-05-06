<?php
/**
 * 主播資料 資源庫
 * @author Weine
 * @date 2020-4-14
 */

namespace App\Repositories;


use App\Entities\UserHost;

class UserHostRepository
{
    protected $userHost;

    public function __construct(UserHost $userHost)
    {
        $this->userHost = $userHost;
    }

    public function updateOrCreate($uid, $data)
    {
        return $this->userHost->updateOrCreate(
            ['id' => $uid],
            $data
        );
    }
}