<?php

namespace App\Modules\Catalog\Requests\Admin;

use App\Modules\Catalog\Support\ContractTypes;

class StoreUnitTypeRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'contract_type' => 'required|'.ContractTypes::rule(),
            'rooms' => 'nullable|in:Room,NoRoom',
        ];
    }
}
