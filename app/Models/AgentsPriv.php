<?php
namespace App\Models;

class AgentsPriv extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_agents_priv';
    protected $primaryKey = 'auto_id';


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function agents()
    {
        return $this->hasOne('App\Models\Agents','id','aid');
    }

}