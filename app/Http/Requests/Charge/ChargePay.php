<?php

namespace App\Http\Requests\Charge;


use App\Http\Requests\VRequest;

class ChargePay extends VRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'price'     => 'required|integer',
            'vipLevel'  => 'required|string',
            'mode_type' => 'required|integer',
            'name'      => 'string|nullable',
        ];
    }

    public function messages()
    {
        return [
            'price.*'     => __('messages.Charge_pay_request.wrong_price'),
            'vipLevel.*'  => __('messages.Charge_pay_request.wrong_vip_level'),
            'mode_type.*' => __('messages.Charge_pay_request.wrong_mode_type'),
            'name.*'      => __('messages.Charge_pay_request.wrong_name'),
        ];
    }
}
