<?php

namespace App\Http\Requests\Admin;

use App\Support\TypeFurnished;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContractUnitRequest extends FormRequest
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
            'unit_type_id' => ['sometimes', 'nullable', 'integer', 'exists:unit_types,id'],
            'unit_usage_id' => ['sometimes', 'nullable', 'integer', 'exists:unit_usages,id'],
            'unit_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'floor_number' => ['sometimes', 'nullable', 'integer'],
            'unit_area' => ['sometimes', 'nullable', 'numeric'],
            'tootal_rooms' => ['sometimes', 'nullable', 'integer'],
            'The_number_of_halls' => ['sometimes', 'nullable', 'integer'],
            'The_number_of_kitchens' => ['sometimes', 'nullable', 'integer'],
            'The_number_of_toilets' => ['sometimes', 'nullable', 'integer'],
            'The_number_of_the_toilet' => ['sometimes', 'nullable', 'integer'],
            'window_ac' => ['sometimes', 'nullable', 'integer'],
            'split_ac' => ['sometimes', 'nullable', 'integer'],
            'electricity_meter_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'water_meter_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'kitchen_tank' => ['sometimes', 'nullable', 'boolean'],
            'furnished' => ['sometimes', 'nullable', 'boolean'],
            'type_furnished' => TypeFurnished::rules(true),
            'electricity_meter' => ['sometimes', 'nullable', 'boolean'],
            'water_meter' => ['sometimes', 'nullable', 'boolean'],
            'electricity_meter_ownership' => ['sometimes', 'nullable', 'in:owner,tenant'],
            'water_meter_ownership' => ['sometimes', 'nullable', 'in:owner,tenant'],
            'Number_parking_spaces' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
