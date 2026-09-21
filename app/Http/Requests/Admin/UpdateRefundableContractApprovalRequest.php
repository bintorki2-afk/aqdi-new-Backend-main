<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateRefundableContractApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['sometimes', 'nullable', 'string', 'max:64'],
            'contract_id' => ['sometimes', 'nullable'],
            'id' => ['sometimes', 'nullable'],
            'action' => ['sometimes', 'nullable', 'string', 'in:approve,reject,retract'],
            'admin_confirmed' => ['sometimes', 'boolean'],
            'refund_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->has('admin_confirmed')
                && ! $this->has('refund_amount')
                && ! $this->has('notes')
                && ! $this->filled('action')) {
                $validator->errors()->add(
                    'admin_confirmed',
                    trans('api.refund_update_requires_field')
                );
            }
        });
    }
}
