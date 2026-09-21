<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\Setting;

/**
 * Resolve electricity/water meter fees for a contract when ownership is tenant.
 */
final class MeterFees
{
    /**
     * @return array{
     *     electricity_meter_fee: float,
     *     water_meter_fee: float,
     *     meter_fees_total: float
     * }
     */
    public static function forContract(Contract $contract, ?Setting $setting = null): array
    {
        $setting ??= Setting::query()->first();

        $electricity = 0.0;
        $water = 0.0;

        if ($setting) {
            $isHousing = $contract->contract_type === 'housing';

            // Contracts created before Step 5 rolled unit ownership up to the contract only carry
            // the ownership on their units: derive it from them when the contract column is empty.
            $electricityOwnership = $contract->electricity_meter_ownership ?? self::ownershipFromUnits($contract, 'electricity_meter_ownership');
            $waterOwnership = $contract->water_meter_ownership ?? self::ownershipFromUnits($contract, 'water_meter_ownership');

            if ($electricityOwnership === 'tenant') {
                $electricity = (float) ($isHousing
                    ? $setting->electricity_meter_fee_housing_tenant
                    : $setting->electricity_meter_fee_commercial_tenant);
            }

            if ($waterOwnership === 'tenant') {
                $water = (float) ($isHousing
                    ? $setting->water_meter_fee_housing_tenant
                    : $setting->water_meter_fee_commercial_tenant);
            }
        }

        $electricity = max(0, $electricity);
        $water = max(0, $water);

        return [
            'electricity_meter_fee' => $electricity,
            'water_meter_fee' => $water,
            'meter_fees_total' => $electricity + $water,
        ];
    }

    private static function ownershipFromUnits(Contract $contract, string $column): ?string
    {
        if (! $contract->exists) {
            return null;
        }

        try {
            $values = $contract->relationLoaded('units')
                ? $contract->units->pluck($column)->filter()->values()
                : $contract->units()->pluck($column)->filter()->values();
        } catch (\Throwable) {
            return null;
        }

        if ($values->isEmpty()) {
            return null;
        }

        return $values->contains('tenant') ? 'tenant' : 'owner';
    }

    public static function totalForContract(Contract $contract, ?Setting $setting = null): float
    {
        return self::forContract($contract, $setting)['meter_fees_total'];
    }
}
