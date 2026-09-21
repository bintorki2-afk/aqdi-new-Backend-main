<?php

namespace App\Modules\Catalog\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreCityRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'name_ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cities', 'name_ar')->where(fn ($q) => $q->where('region_id', $this->input('region_id'))),
            ],
            'name_en' => ['nullable', 'string', 'max:255'],
        ];
    }
}
