<?php

namespace App\Http\Requests\User;


use App\Http\Requests\VRequest;

class UserSetLocale extends VRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'loc' => 'string|nullable',
        ];
    }

    public function messages()
    {
        return [
            'loc.*' => '輸入語系不正確',
        ];
    }
}
