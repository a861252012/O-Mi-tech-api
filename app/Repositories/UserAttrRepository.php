<?php
/**
 * 用戶資料關聯 資源庫
 * @author Weine
 * @date 2020-07-02
 */

namespace App\Repositories;


use App\Entities\UserAttr;

class UserAttrRepository
{
    protected $userAttr;

    public function __construct(UserAttr $userAttr)
    {
        $this->userAttr = $userAttr;
    }

    public function getVByK($uid, $k)
    {
        return $this->userAttr->where('uid', $uid)->where('k', $k)->value('v');
    }

    public function updateOrCreate($uid, $k, $v)
    {
        return $this->userAttr->updateOrCreate(
            ['uid' => $uid, 'k' => $k],
            ['v' => $v]
        );
    }
}