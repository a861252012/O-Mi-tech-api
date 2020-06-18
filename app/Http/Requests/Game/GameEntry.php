<?php

namespace App\Http\Requests\Game;


use App\Http\Requests\VRequest;

class GameEntry extends VRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'gp_id'     => 'required|string',
            'game_code' => 'string|nullable',
        ];
    }

    public function messages()
    {
        return [
            'gp_id.*'     => '輸入遊戲商ID不正確',
            'game_code.*' => '輸入遊戲代碼不正確',
        ];
    }
}
