<?php

namespace App\Modules\Catalog\Requests\Admin;

use App\Modules\Catalog\Models\City;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $city = City::query()->findOrFail((int) $this->route('id'));
        $regionId = (int) $this->input('region_id', $city->region_id);

        return [
            'region_id' => ['sometimes', 'required', 'integer', 'exists:regions,id'],
            'name_ar' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('cities', 'name_ar')
                    ->where(fn ($q) => $q->where('region_id', $regionId))
                    ->ignore($city->id),
            ],
            'name_en' => ['nullable', 'string', 'max:255'],
        ];
    }
}
