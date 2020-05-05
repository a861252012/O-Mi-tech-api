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
            'mode_type' => 'required|string',
            'name'      => 'string|nullable',
        ];
    }

    public function messages()
    {
        return [
            'price.*'     => '请输入正确的金额!',
            'vipLevel.*'  => '渠道輸入不正確',
            'mode_type.*' => '支付類型輸入不正確',
            'name.*'      => '名稱輸入不正確',
        ];
    }
}
