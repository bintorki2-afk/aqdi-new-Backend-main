<?php

namespace App\Http\Requests\Admin;

use App\Support\TypeFurnished;
use Illuminate\Foundation\Http\FormRequest;

class SyncContractUnitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('unit_ids') && is_array($this->input('unit_ids'))) {
            $fromIds = collect($this->input('unit_ids'))
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => ['unit_id' => (int) $id])
                ->values()
                ->all();

            $existingUnits = is_array($this->input('units')) ? $this->input('units') : [];
            $this->merge(['units' => array_merge($existingUnits, $fromIds)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'units' => ['required', 'array', 'min:1', 'max:50'],
            'units.*.unit_id' => ['nullable', 'integer', 'exists:real_units,id'],
            'units.*.id' => ['nullable', 'integer', 'exists:real_units,id'],
            'units.*.real_unit_id' => ['nullable', 'integer', 'exists:real_units,id'],
            'units.*.unit_type_id' => ['nullable', 'integer', 'exists:unit_types,id'],
            'units.*.unit_usage_id' => ['nullable', 'integer', 'exists:unit_usages,id'],
            'units.*.unit_number' => ['nullable', 'string', 'max:255'],
            'units.*.floor_number' => ['nullable', 'integer'],
            'units.*.unit_area' => ['nullable', 'numeric'],
            'units.*.tootal_rooms' => ['nullable', 'integer'],
            'units.*.The_number_of_halls' => ['nullable', 'integer'],
            'units.*.The_number_of_kitchens' => ['nullable', 'integer'],
            'units.*.The_number_of_toilets' => ['nullable', 'integer'],
            'units.*.The_number_of_the_toilet' => ['nullable', 'integer'],
            'units.*.window_ac' => ['nullable', 'integer'],
            'units.*.split_ac' => ['nullable', 'integer'],
            'units.*.electricity_meter_number' => ['nullable', 'string', 'max:255'],
            'units.*.water_meter_number' => ['nullable', 'string', 'max:255'],
            'units.*.kitchen_tank' => ['nullable', 'boolean'],
            'units.*.furnished' => ['nullable', 'boolean'],
            'units.*.type_furnished' => TypeFurnished::rules(),
            'units.*.electricity_meter' => ['nullable', 'boolean'],
            'units.*.water_meter' => ['nullable', 'boolean'],
            'units.*.electricity_meter_ownership' => ['nullable', 'in:owner,tenant'],
            'units.*.water_meter_ownership' => ['nullable', 'in:owner,tenant'],
            'units.*.Number_parking_spaces' => ['nullable', 'string', 'max:255'],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer', 'exists:real_units,id'],
        ];
    }
}
