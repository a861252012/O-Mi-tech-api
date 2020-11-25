<?php
/**
 * 金流紀錄 資源庫
 * @author Weine
 * @date 2020-11-20
 */

namespace App\Repositories;


use App\Models\Recharge;

class RechargeRepository
{
    protected $recharge;

    public function __construct(Recharge $recharge)
    {
        $this->recharge = $recharge;
    }

    public function insertTransfer($data)
    {
        return $this->recharge->insert($data);
    }

    public function orderIdExist($orderId)
    {
        return $this->recharge->where('order_id', $orderId)->count();
    }
}