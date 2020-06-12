<?php

namespace App\Http\Resources\Game;

use App\Facades\SiteSer;
use Illuminate\Http\Resources\Json\JsonResource;

class GameListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'gp_id'     => (string)$this->gp_id,
            'game_code' => (string)$this->game_code,
            'game_name' => (string)$this->game_name,
            'game_icon' => SiteSer::config('img_host') . '/' . $this->game_icon . '.png' ?? '',
            'sort'      => (int)$this->sort,
        ];
    }
}
