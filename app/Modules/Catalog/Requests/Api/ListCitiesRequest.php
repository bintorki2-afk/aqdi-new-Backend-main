<?php

namespace App\Modules\Catalog\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ListCitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'region_id' => 'required|exists:regions,id',
        ];
    }
}
