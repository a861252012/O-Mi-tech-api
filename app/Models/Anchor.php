<?php
namespace App\Models;

use App\Traits\SiteSpecific;
class Anchor extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected $table='video_anchor';
    protected $primaryKey = 'id';
    public $timestamps= false;
    protected $guarded = ['id'];

//    /**
//     * �������û��Ĺ������Ϣ
//     *
//     * @return \Illuminate\Database\Eloquent\Relations\HasOne
//     */
//    public function vipGroup()
//    {
//        return $this->hasOne('App\Models\UserGroup','level_id','vip');
//    }
//
//    /**
//     * �������û�����ͨ�ȼ�������
//     *
//     * @return \Illuminate\Database\Eloquent\Relations\HasOne
//     */
//    public function lvGroup()
//    {
//        return $this->hasOne('App\Models\UserGroup','level_id','lv_rich');
//    }
}
