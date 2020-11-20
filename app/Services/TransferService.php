<?php
/**
 * 鑽石轉換 服務
 * @author Weine
 * @date 2020-11-19
 */

namespace App\Services;


use App\Repositories\RechargeRepository;
use App\Repositories\PlatformTransferFailedRepository;

class TransferService
{
    protected $platformTransferFailedRepository;
    protected $rechargeRepository;

    public function __construct(
        PlatformTransferFailedRepository $platformTransferFailedRepository,
        RechargeRepository $rechargeRepository
    ) {
        $this->platformTransferFailedRepository = $platformTransferFailedRepository;
        $this->rechargeRepository = $rechargeRepository;
    }

    public function addFailedLog($data)
    {
        return $this->platformTransferFailedRepository->insertLog($data);
    }

    public function addSuccessLog($user, $data)
    {
        $created = date('Y-m-d H:i:s');
        return $this->rechargeRepository->insertTransfer([
            'uid'      => (int)$user['uid'],
            'points'   => (int)$data['points'],
            'created'  => $created,
            'ttime'    => $created,
            'order_id' => (string)$data['order_id'],
            'pay_type' => 8,
            'origin'   => (int)$data['origin'],
            'nickname' => $user['nickname'],
            'site_id'  => $user['site_id'],
            'ip'       => '',
        ]);
    }
}