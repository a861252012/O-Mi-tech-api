<?php


namespace App\Repositories;


use App\Entities\GameList;

class GameListRepository
{
    protected $gameList;

    public function __construct(GameList $gameList)
    {
        $this->gameList = $gameList;
    }

    public function getList()
    {
        return $this->gameList->where('active', 1)
            ->orderBy('sort', 'desc')
            ->get();
    }
}