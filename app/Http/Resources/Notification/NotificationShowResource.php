<?php
/**
 * 五分鐘開車提醒 resource
 * @author Weine
 * @date 2020-10-20
 */
namespace App\Http\Resources\Notification;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'uid'      => $this->uid,
            'nickname' => $this->userAll->nickname,
            'price'    => $this->points,
            'cover'    => $this->userAll->cover,
            'time'     => $this->countdown,
        ];
    }
}
