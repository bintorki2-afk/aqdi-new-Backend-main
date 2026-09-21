<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\UserCoupon;
use App\Support\DocFee;
use Tests\TestCase;

class UserCouponTest extends TestCase
{
    public function test_percentage_is_on_housing_first_year_fee(): void
    {
        $coupon = new UserCoupon([
            'type' => UserCoupon::TYPE_PERCENTAGE,
            'value' => 10,
            'applies_to' => UserCoupon::APPLIES_ALL,
        ]);
        $contract = new Contract;
        $contract->contract_type = 'housing';

        $this->assertSame(24.9, $coupon->discountAmount($contract));
        $this->assertSame(249.0, DocFee::firstYearFee('housing'));
    }

    public function test_percentage_is_on_commercial_first_year_fee(): void
    {
        $coupon = new UserCoupon([
            'type' => UserCoupon::TYPE_PERCENTAGE,
            'value' => 10,
            'applies_to' => UserCoupon::APPLIES_ALL,
        ]);
        $contract = new Contract;
        $contract->contract_type = 'commercial';

        $this->assertSame(34.9, $coupon->discountAmount($contract));
    }

    public function test_fixed_amount_is_capped_at_first_year_fee(): void
    {
        $coupon = new UserCoupon([
            'type' => UserCoupon::TYPE_FIXED,
            'value' => 300,
            'applies_to' => UserCoupon::APPLIES_HOUSING,
        ]);
        $contract = new Contract;
        $contract->contract_type = 'housing';

        $this->assertSame(249.0, $coupon->discountAmount($contract));
    }

    public function test_housing_coupon_does_not_apply_to_commercial(): void
    {
        $coupon = new UserCoupon([
            'type' => UserCoupon::TYPE_PERCENTAGE,
            'value' => 10,
            'applies_to' => UserCoupon::APPLIES_HOUSING,
        ]);

        $this->assertTrue($coupon->appliesToContractType('housing'));
        $this->assertFalse($coupon->appliesToContractType('commercial'));
        $this->assertTrue((new UserCoupon(['applies_to' => UserCoupon::APPLIES_ALL]))->appliesToContractType('commercial'));
    }
}
