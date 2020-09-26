<?php
/**
 * 動態站點配置
 * @author Weine
 * @date 2020-09-11
 */
namespace App\Entities;

use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;

class SiteAttr extends Model
{
    use HasCompositePrimaryKey;

    protected $table = 'video_site_attr';

    protected $primaryKey = ['site_id', 'k'];

    protected $fillable = ['site_id', 'k', 'v'];

    public $incrementing = false;
}
