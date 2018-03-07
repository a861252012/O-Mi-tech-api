<?php
namespace App\Models;

/**
 * 坐骑表对应的模型
 * @author Halin <[<email address>]>
 * Class Messages
 * @package App\Models
 */
class Goods extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 坐骑表
     * @var string
     */
    protected $table = 'video_goods';
    protected $primaryKey = 'gid';

    /**
     * 获取贵族信息
     * 关联用户组等级表
     */
    public function mountGroup()
    {
        return $this->hasOne('App\Models\UserGroup','mount','gid');
    }

}