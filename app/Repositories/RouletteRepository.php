<?php

namespace App\Repositories;

use App\Entities\Roulette;

class RouletteRepository
{
    protected $roulette;

    public function __construct(Roulette $roulette)
    {
        $this->roulette = $roulette;
    }

    public function getHistory($uid, $amount, $startTime, $endTime)
    {
        $query = $this->roulette::query()->where('uid', $uid);

        if (!empty($startTime) && !empty($endTime)) {
            $query->whereBetween('created_at', [$startTime, $endTime]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($amount);
    }
}