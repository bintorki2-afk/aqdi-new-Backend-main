<?php

namespace App\Http\Requests\Api\V2;

use App\Support\ContractV2ValidationMessages;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseApiV2Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        app()->setLocale('ar');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ContractV2ValidationMessages::attributes();
    }

    /**
     * @param  list<string>  $fields
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    protected function contractV2ArabicMessages(array $fields, array $extra = []): array
    {
        return ContractV2ValidationMessages::messagesFor($fields, $extra);
    }

    protected function failedValidation(Validator $validator): void
    {
        app()->setLocale('ar');

        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first() ?: 'البيانات المدخلة غير صحيحة.',
            'code' => 422,
            'success' => false,
            // Additive: per-field messages so clients can highlight the offending input.
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}

