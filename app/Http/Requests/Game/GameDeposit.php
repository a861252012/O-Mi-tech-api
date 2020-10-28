<?php

namespace App\Http\Requests\Game;


use App\Http\Requests\VRequest;

class GameDeposit extends VRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'uid'    => 'required|integer',
            'amount' => 'required|integer',
            'ot'     => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'uid.*'    => '輸入uid錯誤',
            'amount.*' => '輸入金額不正確',
            'ot.*'     => '輸入資料不正確',
        ];
    }
}
