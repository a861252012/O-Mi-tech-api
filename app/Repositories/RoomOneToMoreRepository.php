<?php
/**
 * 一對多主播房間 資源庫
 * @author Weine
 * @date 2020-10-19
 */

namespace App\Repositories;


use App\Models\RoomOneToMore;

class RoomOneToMoreRepository
{
    protected $roomOneToMore;
    
    public function __construct(RoomOneToMore $roomOneToMore)
    {
        $this->roomOneToMore = $roomOneToMore;
    }
    
    public function getListInFiveMinutes()
    {
        $time = time();
        $now = date('Y-m-d H:i:s', $time);
        $after = date('Y-m-d H:i:s', strtotime('+5 minutes', $time));
        
        return $this->roomOneToMore->whereBetween('starttime', [$now, $after])
                ->where('status', 0)
                ->selectRaw('*, TIMESTAMPDIFF(SECOND, now(), starttime) as countdown')
                ->get();
    }
}