<?php

namespace App\Modules\Contracts\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'contract_type' => 'required|in:housing,commercial',
            'real_id' => [
                'nullable',
                Rule::exists('real_estates', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
                Rule::requiredIf($this->is_real == 1),
            ],
            'real_units_id' => [
                'nullable',
                Rule::exists('real_units', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
                Rule::requiredIf($this->is_real == 1),
            ],
        ];
    }
}
