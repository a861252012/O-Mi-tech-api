<?php
/**
 * 我的守護資訊 resource
 * @author Weine
 * @date 2020/02/18
 */

namespace App\Http\Resources\Guardian;

use App\Facades\SiteSer;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianMyInfoResource extends JsonResource
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
            'faq'                 => SiteSer::config('cdn_host') . '/' . SiteSer::config('publish_version') . '/static/faq/guardian.html',
            'guard_id'            => $this->guard_id,
            'guardian_name'       => __('messages.Guardian.name.' . $this->guard_id),
            'last_activate_date'  => $this->guardian()->where('pay_type', 1)->max('pay_date'),
            'last_renewal_date'   => $this->guardian()->where('pay_type', 2)->max('pay_date'),
            'expire_date'         => date('Y-m-d', strtotime("-1 day", strtotime($this->guard_end))),
            'hidden'              => $this->hidden,
            'renewal_count'       => $this->guardian()->where('pay_type', 2)->count(),
            'guardian_permission' => collect($this->guardianInfo)->except([
                'id',
                'name',
                'activate',
                'renewal',
                'created_at',
                'updated_at'
            ])
        ];
    }
}
