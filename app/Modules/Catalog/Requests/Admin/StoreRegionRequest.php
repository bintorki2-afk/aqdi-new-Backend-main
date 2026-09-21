<?php

namespace App\Modules\Catalog\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreRegionRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('regions', 'name_ar')],
            'name_en' => ['nullable', 'string', 'max:255'],
        ];
    }
}
