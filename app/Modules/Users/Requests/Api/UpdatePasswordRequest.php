<?php

namespace App\Modules\Users\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'password' => 'required|confirmed|string|min:8',
        ];
    }
}
