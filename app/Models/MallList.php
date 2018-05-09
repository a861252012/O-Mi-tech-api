<?php
namespace App\Models;

use App\Traits\SiteSpecific;
/**
 * 用户送礼表对应的模型
 * @author Halin <[<email address>]>
 * Class Messages
 * @package App\Models
 */
class MallList extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 送礼表消费记录
     * @var string
     */
    use SiteSpecific;
    protected $table = 'video_mall_list';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps= false;

    /**
     * 获取商品对应的信息
     * 关联用户组等级表
     */
    public function goods()
    {
        return $this->hasOne('App\Models\Goods','gid','gid');
    }

}