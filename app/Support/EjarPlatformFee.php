<?php

namespace App\Support;

use App\Models\Contract;

/**
 * رسوم منصة إيجار (تكلفة عقدي) حسب مدة العقد ونوعه.
 * القاعدة: أي جزء من سنة = سنة كاملة.
 */
final class EjarPlatformFee
{
    public const HOUSING_FIRST_YEAR = 125.0;

    public const HOUSING_EXTRA_YEAR = 125.0;

    public const COMMERCIAL_FIRST_YEAR = 200.0;

    public const COMMERCIAL_EXTRA_YEAR = 400.0;

    public const PERIOD_MONTHS = [
        'شهري' => 1,
        'ربع سنوي' => 3,
        'نصف سنوي' => 6,
        'سنوي' => 12,
    ];

    public static function totalMonths(int $years = 0, int $months = 0): int
    {
        return max(0, $years) * 12 + max(0, $months);
    }

    public static function billableYears(int $totalMonths): int
    {
        if ($totalMonths <= 0) {
            return 0;
        }

        return (int) ceil($totalMonths / 12);
    }

    public static function firstYearFee(string $contractType): float
    {
        return $contractType === 'commercial'
            ? self::COMMERCIAL_FIRST_YEAR
            : self::HOUSING_FIRST_YEAR;
    }

    public static function extraYearFee(string $contractType): float
    {
        return $contractType === 'commercial'
            ? self::COMMERCIAL_EXTRA_YEAR
            : self::HOUSING_EXTRA_YEAR;
    }

    public static function amount(int $totalMonths, string $contractType): float
    {
        $years = self::billableYears($totalMonths);

        if ($years <= 0) {
            return 0.0;
        }

        if ($contractType === 'commercial') {
            return self::COMMERCIAL_FIRST_YEAR + ($years - 1) * self::COMMERCIAL_EXTRA_YEAR;
        }

        return $years * self::HOUSING_FIRST_YEAR;
    }

    public static function monthsFromPeriod(?string $period): ?int
    {
        if ($period === null || $period === '') {
            return null;
        }

        return self::PERIOD_MONTHS[$period] ?? null;
    }

    public static function resolveTotalMonths(Contract $contract): int
    {
        $storedTotal = (int) ($contract->total_months ?? 0);
        if ($storedTotal > 0) {
            return $storedTotal;
        }

        $fromDuration = self::totalMonths(
            (int) ($contract->duration_years ?? 0),
            (int) ($contract->duration_months ?? 0)
        );
        if ($fromDuration > 0) {
            return $fromDuration;
        }

        $period = null;
        if ($contract->relationLoaded('contractTermInYears')) {
            $period = $contract->contractTermInYears?->period;
        } elseif (! empty($contract->contract_term_in_years)) {
            $period = $contract->contractTermInYears()->value('period');
        }

        $fromPeriod = self::monthsFromPeriod(is_string($period) ? $period : null);
        if ($fromPeriod !== null) {
            return $fromPeriod;
        }

        return 12;
    }

    public static function forContract(Contract $contract): float
    {
        return self::amount(
            self::resolveTotalMonths($contract),
            (string) $contract->contract_type
        );
    }
}
