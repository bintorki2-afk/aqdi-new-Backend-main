<?php

namespace App\Http\Resources\Api\V2\RealEstate;

use App\Http\Resources\Api\V2\UnitResource;
use Illuminate\Http\Request;

/**
 * Step 3 response: step 2 + units list.
 */
class Step3RealEstateResource extends Step2RealEstateResource
{
    public function toArray(Request $request): array
    {
        $units = $this->relationLoaded('units')
            ? $this->units
            : $this->units()->with(['unitType', 'unitUsage', 'realEstate'])->get();

        $first = $units->first();

        return array_merge(parent::toArray($request), [
            'units' => UnitResource::collection($units),
            'units_count' => $units->count(),
            'Number_of_units_already_existence' => (string) $units->count(),
            'unit_type_id' => $first?->unit_type_id,
            'unit_usage_id' => $first?->unit_usage_id,
            'unit_number' => $first?->unit_number,
            'floor_number' => $first?->floor_number,
            'unit_area' => $first?->unit_area,
            'step' => $this->step,
        ]);
    }
}
