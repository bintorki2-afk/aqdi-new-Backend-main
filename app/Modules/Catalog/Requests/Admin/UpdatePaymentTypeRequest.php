<?php

namespace App\Modules\Catalog\Requests\Admin;

use App\Modules\Catalog\Support\ContractTypes;
use Illuminate\Validation\Rule;

class UpdatePaymentTypeRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'contract_type' => ['sometimes', Rule::in(ContractTypes::all())],
            'notes' => ['nullable', 'string'],
        ];
    }
}
