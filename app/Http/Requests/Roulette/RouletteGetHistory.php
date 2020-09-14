<?php

namespace App\Http\Requests\Roulette;


use App\Http\Requests\VRequest;

class RouletteGetHistory extends VRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'amount'    => 'numeric|required',
            'startTime' => 'date|nullable',
            'endTime'   => 'date|nullable',
            'page'      => 'numeric|nullable',
        ];
    }

    public function messages()
    {
        return [
            'amount.*'    => __('messages.Guardian_buy_request.type_error'),
            'startTime.*' => __('messages.Guardian_buy_request.type_error'),
            'endTime.*'   => __('messages.Guardian_buy_request.type_error'),
            'page.*'      => __('messages.Guardian_buy_request.type_error'),
        ];
    }
}
