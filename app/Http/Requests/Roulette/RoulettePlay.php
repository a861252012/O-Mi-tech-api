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
            'rid' => 'required|numeric',
            'cnt' => 'nullable|integer',
        ];
    }

    public function messages()
    {
        return [
            'rid.*' => __('messages.Roulette.play.room_id_error'),
            'cnt.*' => __('messages.Roulette.play.count_error'),
        ];
    }
}
