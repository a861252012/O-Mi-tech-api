<?php
namespace App\Models;

use App\Traits\SiteSpecific;
/**
 * 用户购买用户组的记录
 *
 * Class Messages
 * @package App\Models
 */
class HostAudit extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 消息表
     * @var string
     */
    use   SiteSpecific;
    protected  $table='video_host_audit';
    protected $primaryKey = 'auid';

    protected $guarded = ['auid'];

    public $timestamps= false;

}