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

//        $query = $this->rouletteHistory->where('uid', $uid);
//
//        if (!empty($startTime) && !empty($endTime)) {
//            $query->whereBetween('created_at', [$startTime, $endTime]);
//        }
//
//        return $query->orderBy('created_at', 'desc')->paginate($amount);
    }
}