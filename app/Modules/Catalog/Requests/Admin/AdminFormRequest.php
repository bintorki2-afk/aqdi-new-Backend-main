<?php

namespace App\Modules\Catalog\Requests\Admin;

use App\Shared\Responses\JsonEncoding;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class AdminFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'code' => 422,
            'success' => false,
            'errors' => $validator->errors(),
        ], 422, [], JsonEncoding::OPTIONS));
    }
}
