<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReceivedContractStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeContractReceivedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            return;
        }

        $raw = $this->input('status');
        if ($raw === null || $raw === '') {
            return;
        }

        if ($raw instanceof ReceivedContractStatus) {
            return;
        }

        $normalized = ReceivedContractStatus::tryFromFlexible((string) $raw);
        if ($normalized !== null) {
            $this->merge(['status' => $normalized->value]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(ReceivedContractStatus::class)],
            'date_of_received' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasNotesKey = array_key_exists('notes', $this->all());

            if (
                ! $this->filled('status')
                && ! $this->filled('date_of_received')
                && ! $hasNotesKey
            ) {
                $validator->errors()->add(
                    'status',
                    trans('api.received_contract_update_requires_field')
                );
            }
        });
    }
}
