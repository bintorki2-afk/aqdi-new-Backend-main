<?php

namespace App\Http\Requests\Admin;

use App\Models\MarketingServicePage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMarketingServicePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('path')) {
            $this->merge(['path' => MarketingServicePage::normalizePath($this->input('path'))]);
        }
        if ($this->has('status')) {
            $this->merge(['status' => strtolower((string) $this->input('status'))]);
        }
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'path' => [
                'sometimes',
                'required',
                'string',
                'max:191',
                Rule::unique('marketing_service_pages', 'path')->ignore($id),
            ],
            'target_keyword' => ['nullable', 'string', 'max:191'],
            'status' => ['sometimes', 'required', Rule::in(array_keys(MarketingServicePage::STATUSES))],
            'body' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'العنوان مطلوب.',
            'path.required' => 'المسار مطلوب.',
            'path.unique' => 'المسار مستخدم مسبقاً.',
            'status.in' => 'الحالة غير صالحة.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $message = collect($validator->errors()->all())->first() ?: 'البيانات غير صالحة.';

        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
            'message' => $message,
            'code' => 422,
            'success' => false,
        ], 422));
    }
}
