<?php
namespace App\Models;

/**
 * 主播提款流水号
 * @author Halin <[<email address>]>
 * Class Messages
 * @package App\Models
 */
class WithDrawalList extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 主播体现记录
     * @var string
     */
    protected $table = 'video_withdrawal_list';
    protected $primaryKey = 'id';
    /**
     * 屏蔽create_at  and update_at
     * @var bool
     */
    public $timestamps= false;



}