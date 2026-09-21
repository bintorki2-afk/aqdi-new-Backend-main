<?php

namespace App\Modules\Catalog\Requests\Admin;

use App\Modules\Catalog\Support\ContractTypes;
use Illuminate\Validation\Rule;

class StorePaymentTypeRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'contract_type' => ['required', Rule::in(ContractTypes::all())],
            'notes' => ['nullable', 'string'],
        ];
    }
}
