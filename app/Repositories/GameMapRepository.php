<?php
/**
 * 遊戲id關聯 資源庫
 * @author Weine
 * @date 2020-10-08
 */

namespace App\Repositories;

use App\Entities\GameMap;

class GameMapRepository
{
    protected $gameMap;
    
    public function __construct(GameMap $gameMap)
    {
        $this->gameMap = $gameMap;
    }
    
    public function updateOrCreate($condition, $data)
    {
        return $this->gameMap->updateOrCreate($condition, $data);
    }
}