<?php

namespace App\Repositories;

use App\Models\UserGroup;
use App\Models\UserGroupPermission;

class UserGroupRepository
{
    public function __construct(
        UserGroup $userGroup,
        UserGroupPermission $userGroupPermission
    ) {
        $this->userGroup = $userGroup;
        $this->userGroupPermission = $userGroupPermission;
    }

    public function getLevelInfoByType($type)
    {
        return $this->userGroup->where('type', $type)->get();
    }
}
