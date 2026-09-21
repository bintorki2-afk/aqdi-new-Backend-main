<?php

namespace App\Http\Requests\Admin;

use App\Support\TypeFurnished;
use Illuminate\Foundation\Http\FormRequest;

class StoreContractUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'unit_id' => ['nullable', 'integer', 'exists:real_units,id'],
            'id' => ['nullable', 'integer', 'exists:real_units,id'],
            'real_unit_id' => ['nullable', 'integer', 'exists:real_units,id'],
            'unit_type_id' => ['nullable', 'integer', 'exists:unit_types,id'],
            'unit_usage_id' => ['nullable', 'integer', 'exists:unit_usages,id'],
            'unit_number' => ['nullable', 'string', 'max:255'],
            'floor_number' => ['nullable', 'integer'],
            'unit_area' => ['nullable', 'numeric'],
            'tootal_rooms' => ['nullable', 'integer'],
            'The_number_of_halls' => ['nullable', 'integer'],
            'The_number_of_kitchens' => ['nullable', 'integer'],
            'The_number_of_toilets' => ['nullable', 'integer'],
            'The_number_of_the_toilet' => ['nullable', 'integer'],
            'window_ac' => ['nullable', 'integer'],
            'split_ac' => ['nullable', 'integer'],
            'electricity_meter_number' => ['nullable', 'string', 'max:255'],
            'water_meter_number' => ['nullable', 'string', 'max:255'],
            'kitchen_tank' => ['nullable', 'boolean'],
            'furnished' => ['nullable', 'boolean'],
            'type_furnished' => TypeFurnished::rules(),
            'electricity_meter' => ['nullable', 'boolean'],
            'water_meter' => ['nullable', 'boolean'],
            'electricity_meter_ownership' => ['nullable', 'in:owner,tenant'],
            'water_meter_ownership' => ['nullable', 'in:owner,tenant'],
            'Number_parking_spaces' => ['nullable', 'string', 'max:255'],
        ];
    }
}
