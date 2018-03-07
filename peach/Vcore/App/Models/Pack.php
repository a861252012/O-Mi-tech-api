<?php
namespace App\Models;

/**
 * 用户背包表
 * @author Halin <[<email address>]>
 * Class Messages
 * @package App\Models
 */
class Pack extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 背包表
     * @var string
     */
    protected $table = 'video_pack';
    // protected $primaryKey = ['uid,gid'];
    protected $primaryKey = 'uid';
    protected $fillable = ['uid','gid','num','expires'];
    public $timestamps= false;


    /**
     * 获取贵族信息
     * 关联用户组等级表
     */
    public function mountGroup()
    {
        return $this->hasOne('App\Models\Goods','gid','gid');
    }

}
