<?php
namespace App\Models;
use App\Traits\SiteSpecific;
/**
 * 游戏中历史赛车游戏投注信息的模型
 *
 * Class Messages
 * @package App\Models
 */
class CarGameBetBak extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 历史赛车游戏投注信息
     * @var string
     */
    use SiteSpecific;
    protected  $table='video_cargame_bet_bak';
    protected $primaryKey = 'auto_id';

    protected $guarded = ['auto_id'];

    public $timestamps= false;

    /**
     * 关联的赛车游戏
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function game()
    {
        return $this->hasOne('App\Models\CarGame', 'gameid','gameid');
    }

    /**
     * 玩儿游戏对应的用户信息
     * 一对一
     *
     * <p>
     * 关联用户表
     * 在获取时用 with('sendUser') 用于twig 中可以直接调用
     * </p>
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function playGameUser()
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