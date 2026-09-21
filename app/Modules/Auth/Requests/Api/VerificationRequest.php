<?php

namespace App\Modules\Auth\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile' => 'required',
            'verification_code' => 'required',
        ];
    }
}
