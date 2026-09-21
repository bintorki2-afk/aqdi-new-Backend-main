<?php

namespace App\Services;

use App\Models\RealEstate;
use App\Models\UnitsReal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RealEstateUnitsService
{
    /**
     * Replace property units: create new real_units and/or attach existing ones.
     *
     * @param  list<array<string, mixed>>  $unitPayloads
     * @return list<UnitsReal>
     */
    public function syncForRealEstate(RealEstate $realEstate, array $unitPayloads, int $userId): array
    {
        if ($unitPayloads === []) {
            throw new InvalidArgumentException('At least one unit is required.');
        }

        $realEstateId = (int) $realEstate->id;

        return DB::transaction(function () use ($realEstate, $unitPayloads, $userId, $realEstateId) {
            $previousUnitIds = UnitsReal::query()
                ->where('real_estates_units_id', $realEstateId)
                ->where('user_id', $userId)
                ->pluck('id')
                ->all();

            $units = [];
            $keptIds = [];

            foreach ($unitPayloads as $payload) {
                $unit = $this->resolveOrCreateUnit($payload, $userId, $realEstateId, $realEstate);

                if ((int) $unit->real_estates_units_id !== $realEstateId) {
                    $unit->forceFill(['real_estates_units_id' => $realEstateId])->save();
                }

                $units[] = $unit->load(['unitType', 'unitUsage', 'realEstate']);
                $keptIds[] = $unit->id;
            }

            $removedIds = array_diff($previousUnitIds, $keptIds);
            if ($removedIds !== []) {
                UnitsReal::query()
                    ->whereIn('id', $removedIds)
                    ->where('user_id', $userId)
                    ->update(['real_estates_units_id' => null]);
            }

            $realEstate->syncNumberOfUnitsInRealestate($keptIds[0] ?? null);
            $fresh = $realEstate->fresh();
            if ($fresh->number_of_units_in_realestate === null || $fresh->number_of_units_in_realestate === '') {
                $fresh->update(['number_of_units_in_realestate' => (string) count($units)]);
            }

            return $units;
        });
    }

    /**
     * Append one or more units to a property (does not remove existing units).
     *
     * @param  list<array<string, mixed>>  $unitPayloads
     * @return list<UnitsReal>
     */
    public function attachToRealEstate(RealEstate $realEstate, array $unitPayloads, int $userId): array
    {
        if ($unitPayloads === []) {
            throw new InvalidArgumentException('At least one unit is required.');
        }

        $realEstateId = (int) $realEstate->id;

        return DB::transaction(function () use ($realEstate, $unitPayloads, $userId, $realEstateId) {
            $units = [];

            foreach ($unitPayloads as $payload) {
                $unit = $this->resolveOrCreateUnit($payload, $userId, $realEstateId, $realEstate);

                if ((int) $unit->real_estates_units_id !== $realEstateId) {
                    $unit->forceFill(['real_estates_units_id' => $realEstateId])->save();
                }

                $units[] = $unit->load(['unitType', 'unitUsage', 'realEstate']);
            }

            $totalCount = UnitsReal::query()
                ->where('real_estates_units_id', $realEstateId)
                ->where('user_id', $userId)
                ->count();

            $realEstate->syncNumberOfUnitsInRealestate($units[0]->id ?? null);
            $fresh = $realEstate->fresh();
            if ($fresh->number_of_units_in_realestate === null || $fresh->number_of_units_in_realestate === '') {
                $fresh->update(['number_of_units_in_realestate' => (string) $totalCount]);
            }

            return $units;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveOrCreateUnit(array $payload, int $userId, int $realEstateId, RealEstate $realEstate): UnitsReal
    {
        $existingId = $payload['unit_id'] ?? $payload['id'] ?? $payload['real_unit_id'] ?? null;

        if ($existingId) {
            $unit = UnitsReal::query()
                ->whereKey((int) $existingId)
                ->where('user_id', $userId)
                ->first();

            if (! $unit) {
                throw new InvalidArgumentException(trans('api.not_have_unit') ?: 'Unit not found.');
            }

            return $unit;
        }

        $data = $this->normalizeUnitPayload($payload, $userId, $realEstateId, $realEstate);

        return UnitsReal::query()->create(UnitsReal::attributesForApi($data));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeUnitPayload(array $payload, int $userId, int $realEstateId, RealEstate $realEstate): array
    {
        $toilets = $payload['The_number_of_toilets']
            ?? $payload['The_number_of_the_toilet']
            ?? null;

        $data = [
            'real_estates_units_id' => $realEstateId,
            'user_id' => $userId,
            'contract_type' => $payload['contract_type'] ?? $realEstate->contract_type,
            'unit_type_id' => $payload['unit_type_id'] ?? null,
            'unit_usage_id' => $payload['unit_usage_id'] ?? null,
            'unit_number' => $payload['unit_number'] ?? null,
            'floor_number' => $payload['floor_number'] ?? null,
            'unit_area' => $payload['unit_area'] ?? null,
            'tootal_rooms' => $payload['tootal_rooms'] ?? null,
            'The_number_of_halls' => $payload['The_number_of_halls'] ?? null,
            'The_number_of_kitchens' => $payload['The_number_of_kitchens'] ?? null,
            'The_number_of_toilets' => $toilets,
            'window_ac' => $payload['window_ac'] ?? null,
            'split_ac' => $payload['split_ac'] ?? null,
            'electricity_meter_number' => $payload['electricity_meter_number'] ?? null,
            'water_meter_number' => $payload['water_meter_number'] ?? null,
            'kitchen_tank' => (int) filter_var(
                $payload['kitchen_tank'] ?? $payload['kitchen_cabinets'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            ),
            'furnished' => (int) filter_var($payload['furnished'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'type_furnished' => \App\Support\TypeFurnished::normalize($payload['type_furnished'] ?? null),
            'electricity_meter' => (int) filter_var($payload['electricity_meter'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'water_meter' => (int) filter_var($payload['water_meter'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'electricity_meter_ownership' => $this->nullableOwnership($payload['electricity_meter_ownership'] ?? null),
            'water_meter_ownership' => $this->nullableOwnership($payload['water_meter_ownership'] ?? null),
        ];

        if (array_key_exists('Number_parking_spaces', $payload)) {
            $data['Number_parking_spaces'] = $payload['Number_parking_spaces'];
        }

        return $data;
    }

    private function nullableOwnership(mixed $value): ?string
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return (string) $value;
    }
}
