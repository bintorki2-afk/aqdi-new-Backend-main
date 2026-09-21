<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReceivedContractStatus;
use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeContractReceivedRequest extends FormRequest
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
            'contract_id' => [
                'required',
                'integer',
                Rule::exists(Contract::class, 'id')->where(fn ($q) => $q->where('is_delete', false)),
            ],
            'status' => ['sometimes', Rule::enum(ReceivedContractStatus::class)],
            'date_of_received' => ['sometimes', 'nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
