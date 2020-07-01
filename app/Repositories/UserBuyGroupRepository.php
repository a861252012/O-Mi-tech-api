<?php


namespace App\Repositories;

use App\Models\UserBuyGroup;

class UserBuyGroupRepository
{
    public function __construct(UserBuyGroup $userBuyGroup)
    {
        $this->userBuyGroup = $userBuyGroup;
    }

    //寫入貴族紀錄
    public function insertRecord($record)
    {
        return $this->userBuyGroup->insert($record);
    }
}
