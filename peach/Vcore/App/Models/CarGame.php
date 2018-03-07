<?php
namespace App\Models;

/**
 * 赛车游戏的模型
 *
 * Class Messages
 * @package App\Models
 */
class CarGame extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 赛车游戏
     * @var string
     */
    protected  $table='video_cargame';
    protected $primaryKey = 'gameid';

    protected $guarded = ['gameid'];

    public $timestamps= false;

    /**
     * 游戏庄家对应的用户信息
     * 一对一
     *
     * <p>
     * 关联用户表
     * 在获取时用 with('sendUser') 用于twig 中可以直接调用
     * </p>
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function gameMasterUser()
    {
        return $this->hasOne('App\Models\Users','uid','uid');
    }


    /**
     * 游戏所在房间对应的用户信息
     * 一对一
     *
     * <p>
     * 关联用户表
     * 在获取时用 with('sendUser') 用于twig 中可以直接调用
     * </p>
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function gameRoomUser()
    {
        return $this->hasOne('App\Models\Users','uid','rid');
    }

}