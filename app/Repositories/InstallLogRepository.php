<?php
/**
 * 安裝資訊 資源庫
 * @author Weine
 * @date 2020-3-30
 */

namespace App\Repositories;


use App\Entities\InstallLog;

class InstallLogRepository
{
    protected $installLog;

    public function __construct(InstallLog $installLog)
    {
        $this->installLog = $installLog;
    }

    public function insertLog($data)
    {
        return $this->installLog->insert($data);
    }
}