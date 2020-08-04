<?php


namespace App\Http\Requests\BackPack;

use App\Http\Requests\VRequest;

class UseItem extends VRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'numeric|nullable|min:1',
        ];
    }

    public function messages()
    {
        return [
            'id.numeric' => ':attribute 參數類型不正確',
        ];
    }

}
