<?php
/**
 * 代理 資源庫
 * date 2020-12-25
 */

namespace App\Repositories;


use App\Models\Agents;

class AgentsRepository
{
    protected $agents;

    public function __construct(Agents $agents)
    {
        $this->agents = $agents;
    }

    public function getDataById($id)
    {
        return $this->agents->where('status', 0)->where('id', $id)->first();
    }
}