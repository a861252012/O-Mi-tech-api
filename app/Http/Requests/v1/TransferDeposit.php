<?php

namespace App\Http\Requests\v1;


use App\Http\Requests\VRequest;

class TransferDeposit extends VRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'points'    => 'required|numeric|min:1|max:9999',
            'username'  => 'required|string',
            'uuid'      => 'required|numeric',
            'token'     => 'string|nullable',
            'locale'    => 'string|nullable',
            'order_id'  => 'required|string',
            'timestamp' => 'required|integer',
            'origin'    => 'required|integer',
            'sign'      => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'points.required'   => ':attribute 參數錯誤!',
            'points.numeric'    => ':attribute 必須為數字!',
            'points.min'        => ':attribute 不得低於最小下限值!',
            'points.max'        => ':attribute 超過最大上限值!',
            'username.*'        => ':attribute 參數錯誤!',
            'uuid.*'            => ':attribute 參數錯誤!',
            'token.*'           => ':attribute 參數錯誤!',
            'locale.*'          => ':attribute 參數錯誤!',
            'order_id.required' => ':attribute 參數錯誤!',
            'order_id.string'   => ':attribute 必須為字串!',
            'timestamp.*'       => ':attribute 參數錯誤!',
            'origin.*'          => ':attribute 參數錯誤!',
            'sign.*'            => ':attribute 參數錯誤!',
        ];
    }
}
