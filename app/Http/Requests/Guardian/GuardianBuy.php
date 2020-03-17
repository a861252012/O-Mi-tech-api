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
            'guardId'  => 'required|numeric',
            'payType'  => 'required|numeric',
            'daysType' => 'required|numeric',
        ];
    }

    public function messages()
    {
        return [
            'rid.numeric'       => ':attribute 參數類型不正確',
            'guardId.required'  => ':attribute 需必填',
            'guardId.numeric'   => ':attribute 參數類型不正確',
            'payType.required'  => ':attribute 需必填',
            'payType.numeric'   => ':attribute 參數類型不正確',
            'daysType.required' => ':attribute 需必填',
            'daysType.numeric'  => ':attribute 參數類型不正確',
        ];
    }
}
