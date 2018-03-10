<?php
namespace App\Models;

/**
 * 消息表对应的模型
 *
 * Class Messages
 * @package App\Models
 */
class Messages extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 消息表
     * @var string
     */
    protected  $table='video_mail';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    /**
     * 屏蔽create_at  and update_at
     * @var bool
     */
    public $timestamps= false;

    /**
     * 消息发送者对应的用户信息
     * 一对一
     *
     * <p>
     * 关联用户表
     * 在获取时用 with('sendUser') 用于twig 中可以直接调用
     * </p>
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sendUser()
    {
        return $this->hasOne('App\Models\Users','uid','send_uid');
    }
}