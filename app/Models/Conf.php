<?php
namespace App\Models;

/**
 * 配置表
 * @author Halin <[<email address>]>
 * Class Messages
 * @package App\Models
 */
class Conf extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 配置表
     * @var string
     */
    protected $table = 'video_conf';
    protected $primaryKey = 'name';
    public $timestamps= false;



}