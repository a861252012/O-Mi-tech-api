<?php
/**
 * 遊戲列表 服務
 * @author Weine
 * @date 2020-06-02
 */

namespace App\Services;


use App\Http\Resources\Game\GameListResource;
use App\Repositories\GameListRepository;

class GameListService
{
    protected $gameListRepository;

    public function __construct(GameListRepository $gameListRepository)
    {
        $this->gameListRepository = $gameListRepository;
    }

    public function getList()
    {
        return GameListResource::collection($this->gameListRepository->getList());
    }
}