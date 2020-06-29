<?php


namespace App\Http\Resources\BackPack;

use Illuminate\Http\Resources\Json\JsonResource;

class BackPackResource extends JsonResource
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
            'id'            => $this->id,
            'item_id'       => (string)$this->item_id,
            'uid'           => (int)$this->uid,
            'item_type'     => (int)$this->item->item_type,
            'item_name'     => (string)$this->item->item_name,
            'frontend_mode' => (int)$this->item->frontend_mode,
        ];
    }

}