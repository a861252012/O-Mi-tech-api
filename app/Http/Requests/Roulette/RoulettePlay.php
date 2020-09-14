<?php

namespace App\Http\Requests\Roulette;


use App\Http\Requests\VRequest;

class RoulettePlay extends VRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'count' => 'nullable|integer',
        ];
    }

    public function messages()
    {
        return [
            'count.*' => __('messages.Roulette.play.count_error'),
        ];
    }
}
