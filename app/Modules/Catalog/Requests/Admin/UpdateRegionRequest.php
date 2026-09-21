<?php

namespace App\Modules\Catalog\Requests\Admin;

use App\Modules\Catalog\Models\Region;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $region = Region::query()->findOrFail((int) $this->route('id'));

        return [
            'name_ar' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('regions', 'name_ar')->ignore($region->id)],
            'name_en' => ['nullable', 'string', 'max:255'],
        ];
    }
}
