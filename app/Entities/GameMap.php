<?php
/**
 * 遊戲id關聯 實體
 * @author Weine
 * @date 2020-10-8
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class GameMap extends Model
{
    protected $table = 'video_game_map';
    
    protected $fillable = ['gp_id', 'game_id', 'name'];
}
