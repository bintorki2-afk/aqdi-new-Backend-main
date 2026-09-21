<?php

namespace App\Modules\Catalog\Requests\Admin;

use App\Modules\Catalog\Support\ContractTypes;

class StoreReaEstatTypeRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'contract_type' => 'required|'.ContractTypes::rule(),
            'name_ar' => 'required|string|max:255',
        ];
    }
}
