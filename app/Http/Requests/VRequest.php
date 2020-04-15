<?php

namespace App\Http\Requests;

//use App\Traits\ApiOutput;
use Illuminate\Foundation\Http\FormRequest;

abstract class VRequest extends FormRequest
{
//    use ApiOutput;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() { }


    public function messages() { }


    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        try {
            $validator->after(function ($validator) {
//                dd($validator->errors());
                if ($validator->errors()->count()) {
                    $response = [
                        'status' => 0,
                        'msg' => $validator->errors()->first(),
                    ];

                    return response()->json($response)->throwResponse();
                }
//            if ($this->somethingElseIsInvalid()) {
//                $validator->errors()->add('field', 'Something is wrong with this field!');
//            }
            });
        } catch (\Exception $e) {
            report($e);
        }
    }
}
