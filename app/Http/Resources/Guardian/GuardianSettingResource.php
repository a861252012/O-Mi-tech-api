<?php
/**
 * 守護功能設定 resource
 * @author Weine
 * @date 2020/02/17
 */

namespace App\Http\Resources\Guardian;

use Illuminate\Http\Resources\Json\JsonResource;

class GuardianSettingResource extends JsonResource
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
            "id"                => (int)$this->id,
            "name"              => $this->name,
            "activate"          => $this->activate,
            "renewal"           => $this->renewal,
            "activate_notify"   => (boolean)$this->activate_notify,
            "room_notify"       => (boolean)$this->room_notify,
            "all_notify"        => (boolean)$this->all_notify,
            "welcome_notify"    => (boolean)$this->welcome_notify,
            "shot_border"       => (boolean)$this->shot_border,
            "rename"            => (boolean)$this->rename,
            "rename_limit"      => (int)$this->rename_limit,
            "feiping"           => (boolean)$this->feiping,
            "feiping_count"     => (int)$this->feiping_count,
            "chat_bg"           => (boolean)$this->chat_bg,
            "chat_limit"        => (boolean)$this->chat_limit,
            "chat_freq_limit"   => (int)$this->chat_freq_limit,
            "chat_length_limit" => (int)$this->chat_length_limit,
            "forbid"            => (boolean)$this->forbid,
            "forbid_count"      => (int)$this->forbid_count,
            "kick"              => (boolean)$this->kick,
            "kick_count"        => (int)$this->kick_count,
            "show_discount"     => (int)$this->show_discount,
            "hidden"            => (boolean)$this->hidden,
        ];
    }
}
