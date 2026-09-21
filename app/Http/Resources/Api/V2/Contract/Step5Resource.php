<?php

namespace App\Http\Resources\Api\V2\Contract;

use App\Http\Resources\Api\V2\Contract\Concerns\MapsContractStatusFields;
use App\Http\Resources\Api\V2\UnitResource;
use App\Http\Resources\Concerns\WithContractDocumentationDeadline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Step5Resource extends JsonResource
{
    use MapsContractStatusFields;
    use WithContractDocumentationDeadline;

    public function toArray(Request $request): array
    {
        $units = $this->relationLoaded('units')
            ? $this->units
            : $this->units()->with(['unitType', 'unitUsage', 'realEstate'])->get();

        $first = $units->first();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'real_id' => $this->real_id,
            'units' => UnitResource::collection($units),
            'units_count' => $units->count(),
            // First unit aliases for older clients (optional)
            'unit_type_id' => $first?->unit_type_id,
            'unit_usage_id' => $first?->unit_usage_id,
            'unit_number' => $first?->unit_number,
            'floor_number' => $first?->floor_number,
            'unit_area' => $first?->unit_area,
            ...$this->contractStatusFields(),
            'step' => $this->step,
        ];
    }
}
