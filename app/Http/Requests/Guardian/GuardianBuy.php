<?php

namespace App\Http\Requests\Guardian;

use App\Http\Requests\VRequest;

class GuardianBuy extends VRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'rid'      => 'numeric|nullable',
            'guardId'  => 'required|numeric|min:1|max:3',
            'payType'  => 'required|numeric|min:1|max:2',
            'daysType' => 'required|numeric|min:1|max:3',
        ];
    }

    public function messages()
    {
        return [
            'rid.numeric'       => ':attribute ' . __('messages.Guardian_buy_request.type_error'),
            'uid.numeric'       => ':attribute ' . __('messages.Guardian_buy_request.type_error'),
            'guardId.required'  => ':attribute ' . __('messages.Guardian_buy_request.required'),
            'guardId.numeric'   => ':attribute ' . __('messages.Guardian_buy_request.type_error'),
            'payType.required'  => ':attribute ' . __('messages.Guardian_buy_request.required'),
            'payType.numeric'   => ':attribute ' . __('messages.Guardian_buy_request.type_error'),
            'daysType.required' => ':attribute ' . __('messages.Guardian_buy_request.required'),
            'daysType.numeric'  => ':attribute ' . __('messages.Guardian_buy_request.type_error'),
        ];
    }
}
