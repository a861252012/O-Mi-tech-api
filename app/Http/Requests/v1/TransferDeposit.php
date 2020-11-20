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
            'points'    => 'required|integer|min:1',
            'username'  => 'required|string',
            'uuid'      => 'required|string',
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
            'username.*'  => ':attribute 參數錯誤!',
            'uuid.*'      => ':attribute 參數錯誤!',
            'token.*'     => ':attribute 參數錯誤!',
            'locale.*'    => ':attribute 參數錯誤!',
            'order_id.*'  => ':attribute 參數錯誤!',
            'timestamp.*' => ':attribute 參數錯誤!',
            'origin.*'    => ':attribute 參數錯誤!',
            'sign.*'      => ':attribute 參數錯誤!',
        ];
    }
}
