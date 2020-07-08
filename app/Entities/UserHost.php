<?php
/**
 * 主播資訊 實體
 * @author Weine
 * @date 2020-03-17
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class UserHost extends Model
{
    protected $table = 'video_user_host';

    protected $fillable = ['id', 'cover', 'feature', 'content'];

    protected function user()
    {
        return $this->hasOne('App\Models\Users', 'uid');
    }
}
