<?php

namespace App\Http\Resources\Api\V2\RealEstate;

use App\Http\Resources\Api\V2\UnitResource;
use Illuminate\Http\Request;

class RealEstateResource extends Step2RealEstateResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), $this->additionalAttributes());
    }

    /**
     * @return array<string, mixed>
     */
    private function additionalAttributes(): array
    {
        $attributes = $this->resource->getAttributes();

        $extras = [
            'user_id' => $this->user_id,
            'type_real_estate_other' => $this->type_real_estate_other,
            'unit_number' => $this->unit_number,
            'is_deleted' => $this->is_deleted ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->relationLoaded('units')) {
            $extras['units'] = UnitResource::collection($this->units);
            $extras['units_count'] = $this->units->count();
            $extras['Number_of_units_already_existence'] = (string) $this->units->count();
        }

        foreach (['uuid', 'dob_hijri', 'national_num', 'DOB', 'mobile', 'iban_bank'] as $column) {
            if (array_key_exists($column, $attributes)) {
                $extras[$column] = $this->{$column};
            }
        }

        return $extras;
    }
}
