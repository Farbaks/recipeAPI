<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewUser extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required',
            'password' => 'required',
        ];
    }

    // public function messages()
    // {
    //     return response()->json([
    //         'status' => 400,
    //         'message' => 'All fields are required',
    //         'data'=> []
    //     ], 400);
    // }
}
