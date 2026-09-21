<?php

namespace App\Modules\Catalog\Requests\Api;

use App\Modules\Catalog\Support\ContractTypes;
use Illuminate\Foundation\Http\FormRequest;

class FilterByContractTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_type' => 'required|'.ContractTypes::rule(),
        ];
    }
}
