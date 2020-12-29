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
            'origin'  => 'required|numeric',
            'site_id' => 'required|numeric',
            'scode'   => 'string|nullable',
        ];
    }

    public function messages()
    {
        return [
            'origin.*'  => __('messages.ShareInstallLogRequest.origin'),
            'site_id.*' => __('messages.ShareInstallLogRequest.site_id'),
            'scode.*'   => __('messages.ShareInstallLogRequest.scode'),
        ];
    }
}
