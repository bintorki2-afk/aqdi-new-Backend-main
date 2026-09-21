<?php

namespace App\Modules\Catalog\Requests\Admin;

use App\Modules\Catalog\Support\ContractTypes;
use Illuminate\Foundation\Http\FormRequest;

class StoreContractPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'period' => 'required|string',
            'note_ar' => 'required|string',
            'contract_type' => 'required|'.ContractTypes::rule(),
            'price' => 'nullable|numeric|min:0',
        ];
    }
}
