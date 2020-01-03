<?php
/**
 * 登入公告 resource
 * @author Weine
 * @date 2020/1/2
 */
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'device'      => $this->device,
            'type'        => $this->type,
            'interval'    => (string) $this->between,
            'title'       => $this->title,
            'content'     => $this->content,
            'img'         => $this->image,
            'url'         => $this->link,
            'blank'       => $this->blank,
            'create_time' => (string) strtotime($this->created_at),
        ];
    }
}
