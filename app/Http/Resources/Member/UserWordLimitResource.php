<?php

namespace App\Http\Resources\Member;

use Illuminate\Http\Resources\Json\JsonResource;

class UserWordLimitResource extends JsonResource
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
            "gid"        => (int)$this->gid,
            "level_id"   => (int)$this->level_id,
            "level_name" => (string)$this->level_name,
            "chat_limit" => (int)$this->permission->chatlimit,
        ];
    }
}
