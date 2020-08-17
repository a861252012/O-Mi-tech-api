<?php

namespace App\Http\Requests\User;


use App\Http\Requests\VRequest;
use Illuminate\Validation\Rule;

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
            'loc' => ['string', 'nullable', Rule::in(['zh', 'zh_TW', 'zh_HK', 'en'])],
        ];
    }

    public function messages()
    {
        return [
            'loc.*' => '輸入語系不正確',
        ];
    }
}
