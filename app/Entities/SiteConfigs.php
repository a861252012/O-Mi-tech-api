<?php
/**
 * 網站設定 實體
 * @author Weine
 * @date 2019/11/20
 *
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class SiteConfigs extends Model
{
    protected $table = 'video_site_configs';
	public $timestamps = false;
}
