<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Support\EjarPlatformFee;
use Tests\TestCase;

class EjarPlatformFeeTest extends TestCase
{
    public function test_housing_one_year(): void
    {
        $this->assertSame(125.0, EjarPlatformFee::amount(12, 'housing'));
    }

    public function test_housing_one_year_and_one_month(): void
    {
        $this->assertSame(250.0, EjarPlatformFee::amount(13, 'housing'));
    }

    public function test_housing_two_years(): void
    {
        $this->assertSame(250.0, EjarPlatformFee::amount(24, 'housing'));
    }

    public function test_housing_two_years_and_extra_month(): void
    {
        $this->assertSame(375.0, EjarPlatformFee::amount(25, 'housing'));
    }

    public function test_commercial_one_year(): void
    {
        $this->assertSame(200.0, EjarPlatformFee::amount(12, 'commercial'));
    }

    public function test_commercial_one_year_and_one_month(): void
    {
        $this->assertSame(600.0, EjarPlatformFee::amount(13, 'commercial'));
    }

    public function test_commercial_two_years(): void
    {
        $this->assertSame(600.0, EjarPlatformFee::amount(24, 'commercial'));
    }

    public function test_commercial_two_years_and_extra_month(): void
    {
        $this->assertSame(1000.0, EjarPlatformFee::amount(25, 'commercial'));
    }

    public function test_billable_years_round_partial_year_up(): void
    {
        $this->assertSame(1, EjarPlatformFee::billableYears(1));
        $this->assertSame(1, EjarPlatformFee::billableYears(12));
        $this->assertSame(2, EjarPlatformFee::billableYears(13));
        $this->assertSame(0, EjarPlatformFee::billableYears(0));
    }

    public function test_legacy_period_labels_map_to_months(): void
    {
        $this->assertSame(1, EjarPlatformFee::monthsFromPeriod('شهري'));
        $this->assertSame(3, EjarPlatformFee::monthsFromPeriod('ربع سنوي'));
        $this->assertSame(6, EjarPlatformFee::monthsFromPeriod('نصف سنوي'));
        $this->assertSame(12, EjarPlatformFee::monthsFromPeriod('سنوي'));
        $this->assertNull(EjarPlatformFee::monthsFromPeriod('unknown'));
    }

    public function test_uses_stored_total_months_on_contract(): void
    {
        $contract = new Contract;
        $contract->contract_type = 'housing';
        $contract->total_months = 13;

        $this->assertSame(13, EjarPlatformFee::resolveTotalMonths($contract));
        $this->assertSame(250.0, EjarPlatformFee::forContract($contract));
    }

    public function test_falls_back_to_duration_years_and_months(): void
    {
        $contract = new Contract;
        $contract->contract_type = 'commercial';
        $contract->duration_years = 2;
        $contract->duration_months = 1;

        $this->assertSame(25, EjarPlatformFee::resolveTotalMonths($contract));
        $this->assertSame(1000.0, EjarPlatformFee::forContract($contract));
    }

    public function test_defaults_to_one_year_when_duration_is_missing(): void
    {
        $contract = new Contract;
        $contract->contract_type = 'housing';

        $this->assertSame(12, EjarPlatformFee::resolveTotalMonths($contract));
        $this->assertSame(125.0, EjarPlatformFee::forContract($contract));
    }
}
