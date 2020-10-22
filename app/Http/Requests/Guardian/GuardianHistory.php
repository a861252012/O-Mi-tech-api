<?php


namespace App\Http\Requests\Guardian;

use App\Http\Requests\VRequest;

class GuardianHistory extends VRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'startTime' => 'date|nullable',
            'endTime' => 'date|nullable',
        ];
    }

    public function messages()
    {
        return [
            'startTime.date' => ':attribute ' . __('messages.Guardian_buy_request.type_error'),
            'endTime.date' => ':attribute ' . __('messages.Guardian_buy_request.type_error'),
        ];
    }
}