<?php
namespace App\Models;

use App\Traits\SiteSpecific;
/**
 * 关注表对应的模型
 * @author Halin <[<email address>]>
 * Class Messages
 * @package App\Models
 */
class Attention extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 关注表
     * @var string
     */
    use SiteSpecific;
    protected $table = 'video_attention';
    protected $primaryKey = 'fid';



}