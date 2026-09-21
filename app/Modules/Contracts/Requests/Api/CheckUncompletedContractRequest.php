<?php

namespace App\Modules\Contracts\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CheckUncompletedContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'contract_type' => 'required|in:housing,commercial',
        ];
    }
}
