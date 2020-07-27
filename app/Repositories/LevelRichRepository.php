<?php


namespace App\Repositories;

use App\Models\LevelRich;

class LevelRichRepository
{
    public function __construct(LevelRich $levelRich)
    {
        $this->levelRich = $levelRich;
    }

    public function getLevelByGid($gid)
    {
        return $this->levelRich->where('gid', $gid)->first();
    }
}
