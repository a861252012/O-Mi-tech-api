<?php
/**
 * 鑽石交換紀錄 資源庫
 * @author Weine
 * @date 2020-11-19
 */

namespace App\Repositories;


use App\Entities\PlatformTransferFailed;

class PlatformTransferFailedRepository
{
    protected $platformTransferFailed;

    public function __construct(PlatformTransferFailed $platformTransferFailed)
    {
        $this->platformTransferFailed = $platformTransferFailed;
    }

    public function insertLog($data)
    {
        return $this->platformTransferFailed->insert($data);
    }
}