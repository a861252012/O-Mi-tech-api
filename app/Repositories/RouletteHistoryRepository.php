<?php

namespace App\Repositories;

use App\Entities\RouletteHistory;

class RouletteHistoryRepository
{
    protected $rouletteHistory;

    public function __construct(RouletteHistory $rouletteHistory)
    {
        $this->rouletteHistory = $rouletteHistory;
    }

    public function getHistory($uid, $amount, $startTime, $endTime)
    {
        return $this->rouletteHistory->where('uid', $uid)
            ->when(!empty($startTime) && !empty($endTime), function ($query) use ($startTime, $endTime) {
                $query->whereBetween('created_at', [$startTime, $endTime]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($amount);
    }

    public function insertData($data)
    {
        return $this->rouletteHistory->insert($data);
    }
}