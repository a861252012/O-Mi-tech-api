<?php
/**
 * 用戶推廣清單 資源庫
 * @author Weine
 * @date 2020-3-30
 */

namespace App\Repositories;


use App\Entities\UserShare;

class UserShareRepository
{
    protected $userShare;

    public function __construct(UserShare $userShare)
    {
        $this->userShare = $userShare;
    }

    public function insertData($data)
    {
        return $this->userShare->insert($data);
    }

    public function updateDataByUid($uid, $data)
    {
        return $this->userShare->where('uid', $uid)->update($data);
    }
}