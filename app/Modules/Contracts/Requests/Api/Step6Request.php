<?php

namespace App\Modules\Contracts\Requests\Api;

use App\Support\ContractStartingDateInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class Step6Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        ContractStartingDateInput::prepareRequest($this);
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:contracts,id',
            'contract_starting_date' => 'nullable|string',
            'contract_starting_date_day' => 'nullable',
            'contract_starting_date_month' => 'nullable',
            'contract_starting_date_year' => 'nullable',
            'contract_term_in_years' => 'required|exists:contract_periods,id',
            'annual_rent_amount_for_the_unit' => 'required|numeric',
            'payment_type_id' => 'required|exists:payment_types,id',
            'conditions' => 'required|boolean',
            'other_conditions' => 'required_if:conditions,1|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'other_conditions.required_if' => 'حقل شروط أخرى مطلوب عندما تكون الشروط مفعلة.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach (ContractStartingDateInput::validationErrors($this) as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($key, $message);
                }
            }
        });
    }
}
