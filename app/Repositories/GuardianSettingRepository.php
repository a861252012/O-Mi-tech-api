<?php
/**
 * 守護功能設定 資源庫
 * @author Weine
 * @date 2020/05/15
 */

namespace App\Repositories;


use App\Entities\GuardianSetting;

class GuardianSettingRepository
{
    protected $guardianSetting;

    public function __construct(GuardianSetting $guardianSetting)
    {
        $this->guardianSetting = $guardianSetting;
    }

    public function getAll()
    {
        return $this->guardianSetting->all();
    }

    public function getOne($id)
    {
        return $this->guardianSetting->find($id);
    }
}