<?php
namespace App\Models;

class TimecostMallList extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_timecost_mall_list';
    protected $primaryKey = 'id';

    /**
     * ���ѣ�����,��ˮ�б�
     * @param array $where
     * @return mixed
     */
    public function getList($where=[],$live_date=[]){
//        $live_date_ge = \Input::get("live_date_ge",date('Y-m-d',time()-86400*30))." 00:00:00";;
//        $live_date_le = \Input::get("live_date_le",date('Y-m-d'))." 23:59:59";

        $timecost = $this->selectRaw("min(created) as start_time,max(created) as end_time,count(id) as duration,rid,points,SUM(points) as sum_points,send_uid,rec_uid")
            ->with('host')
            ->with('user')
            ->where($where)
             ->whereBetween("live_date",$live_date)
            ->groupBy(['live_id','live_date','send_uid','rec_uid'])
            ->orderBy('created', 'desc');
        return $timecost;
    }
    /*
    * @return \Illuminate\Database\Eloquent\Relations\HasOne
    */
    public function host(){
        return $this->hasOne('App\Models\Users','uid','rec_uid');
    }
    /*
    * @return \Illuminate\Database\Eloquent\Relations\HasOne
    */
    public function user(){
        return $this->hasOne('App\Models\Users','uid','send_uid');
    }


}