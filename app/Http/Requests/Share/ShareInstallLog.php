<?php

namespace App\Http\Requests\Share;


use App\Http\Requests\VRequest;

class ShareInstallLog extends VRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'origin' => 'required|numeric',
            'site_id' => 'required|numeric',
        ];
    }

    public function messages()
    {
        return [
            'origin.*' => '輸入來源不正確',
            'site_id.*' => '輸入站點ID不正確',
        ];
    }
}
