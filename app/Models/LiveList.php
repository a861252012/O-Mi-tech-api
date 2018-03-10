<?php
namespace App\Models;

/**
 * 直播列表
 * @author Halin <[<email address>]>
 * Class Messages
 * @package App\Models
 */
class LiveList extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 直播记录表
     * @var string
     */
    protected $table = 'video_live_list';
    protected $primaryKey = 'id';



}