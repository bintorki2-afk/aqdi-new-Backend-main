<?php

namespace App\Modules\Users\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class UserPropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $units = $this->whenLoaded('units', function () {
            return $this->units
                ->filter(fn ($unit) => (int) ($unit->is_deleted ?? 0) !== 1)
                ->values();
        });

        $userId = (int) $this->user_id;
        $hasDeed = filled($this->image_instrument);
        $hasContracts = $this->relationLoaded('contracts')
            ? $this->contracts->isNotEmpty()
            : false;

        return [
            'id' => $this->id,
            'name' => $this->name_real_estate,
            'name_real_estate' => $this->name_real_estate,
            'instrument_type' => $this->instrument_type,
            'instrument_number' => $this->instrument_number,
            'instrument_history' => $this->instrument_history,
            'contract_type' => $this->contract_type,
            'property_type_id' => $this->property_type_id,
            'property_type_name' => optional($this->propertyType)->name_trans
                ?? optional($this->propertyType)->name_ar,
            'property_usages_id' => $this->property_usages_id,
            'property_usages_name' => optional($this->propertyUsages)->name_trans
                ?? optional($this->propertyUsages)->name_ar,
            'property_city_id' => $this->property_city_id,
            'property_city_name' => optional($this->tenantEntityCity)->name_trans
                ?? optional($this->tenantEntityCity)->name_ar,
            'property_place_id' => $this->property_place_id,
            'property_place_name' => optional($this->tenantEntityRegion)->name_trans
                ?? optional($this->tenantEntityRegion)->name_ar,
            'neighborhood' => $this->neighborhood,
            'street' => $this->street,
            'building_number' => $this->building_number,
            'units_count' => $units instanceof Collection
                ? $units->count()
                : (int) ($this->units_count ?? 0),
            'has_deed' => $hasDeed,
            'deed_url' => $hasDeed
                ? url("/api/admin/users/{$userId}/properties/{$this->id}/deed")
                : null,
            'has_contracts' => $hasContracts,
            'can_delete' => ! $hasContracts,
            'units' => $this->when($units instanceof Collection, function () use ($units) {
                return $units->map(function ($unit) {
                    $hasContracts = $unit->relationLoaded('contracts')
                        ? $unit->contracts->isNotEmpty()
                        : false;
                    $hasLinked = $unit->relationLoaded('linkedContracts')
                        ? $unit->linkedContracts->isNotEmpty()
                        : false;
                    $blocked = $hasContracts || $hasLinked;

                    return [
                        'id' => $unit->id,
                        'unit_number' => $unit->unit_number,
                        'floor_number' => $unit->floor_number,
                        'unit_area' => $unit->unit_area,
                        'unit_type_id' => $unit->unit_type_id,
                        'unit_type_name' => optional($unit->unitType)->name_trans
                            ?? optional($unit->unitType)->name_ar,
                        'unit_usage_id' => $unit->unit_usage_id,
                        'unit_usage_name' => optional($unit->unitUsage)->name_trans
                            ?? optional($unit->unitUsage)->name_ar,
                        'contract_type' => $unit->contract_type ?? optional($unit->realEstate)->contract_type,
                        'has_contracts' => $blocked,
                        'can_delete' => ! $blocked,
                    ];
                })->values();
            }),
        ];
    }
}
