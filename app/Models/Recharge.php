<?php

namespace App\Models;

use App\Traits\SiteSpecific;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 送钱记录对应的模型
 *
 * Class Recharge
 * @package App\Models
 */
class Recharge extends Model
{
    use  SiteSpecific;
    const PAY_TYPE_OWN = 50;
    const SUCCESS = 2;
    const PAY_TYPE_CHONGTI = 1;
    public $timestamps = false;
    /**
     * 表名 消息表
     * @var string
     */
    protected $table = 'video_recharge';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];

    public function getSummaryPaymoney($aid, $mintime, $maxtime)
    {
        $sql = <<<SQL
            SELECT sum(r.paymoney) paymoney
              FROM video_recharge r INNER JOIN
                   video_agent_relationship a ON a.uid=r.uid
             WHERE a.aid={$aid}
               AND r.created BETWEEN '{$mintime}' AND '{$maxtime}'
               AND r.pay_status=2
               AND r.pay_type IN (1, 4, 7)
SQL;
        $q = DB::select($sql)[0];
        return $q->paymoney;
    }
}
