<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRefundableContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('draft_contract_number')) {
            $this->merge([
                'draft_contract_number' => trim((string) $this->input('draft_contract_number')),
            ]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contract_id' => ['required_without:draft_contract_number', 'nullable', 'string', 'max:64'],
            'draft_contract_number' => ['required_without:contract_id', 'nullable', 'string', 'max:32'],
            'refund_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('contract_id') && ! $this->filled('draft_contract_number')) {
                $validator->errors()->add(
                    'contract_id',
                    trans('api.refund_contract_id_required')
                );
            }
        });
    }
}
