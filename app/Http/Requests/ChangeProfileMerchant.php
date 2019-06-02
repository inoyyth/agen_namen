<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeProfileMerchant extends FormRequest
{
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
    public function rules()
    {
        return [
            'name' => 'required', 
            'merchant_name' => 'required',
            'phone' => 'required|numeric',
            'address' => 'required',
            'image' => 'mimes:jpeg,bmp,jpg,png|between:1, 6000'
        ];
    }
}