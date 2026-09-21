<?php

namespace App\Modules\Auth\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile' => 'required',
            'password' => 'required',
            'fcm_token' => 'sometimes',
        ];
    }
}
