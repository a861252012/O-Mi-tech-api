<?php
/**
 * 用戶資料關聯
 * @author Weine
 * @date 2020-07-02
 */
namespace App\Entities;

use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;

class UserAttr extends Model
{
    use HasCompositePrimaryKey;

    protected $table = 'video_user_attr';

    protected $primaryKey = ['uid', 'k'];

    protected $fillable = ['v'];

    public $incrementing = false;
}
