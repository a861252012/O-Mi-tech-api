<?php
/**
 * 用戶所屬代理關聯 實體
 * @author Weine
 * @date 2020-03-31
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class AgentRelationship extends Model
{
    protected $table = 'video_agent_relationship';

    public $timestamps = false;

    public function agent()
    {
        return $this->hasOne('App\Models\Agents', 'id', 'aid');
    }
}
