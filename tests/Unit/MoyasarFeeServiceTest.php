<?php

namespace Tests\Unit;

use App\Services\Admin\MoyasarFeeService;
use App\Models\Setting;
use Tests\TestCase;

class MoyasarFeeServiceTest extends TestCase
{
    private MoyasarFeeService $fees;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fees = new MoyasarFeeService;
    }

    public function test_mada_uses_mada_percent_plus_fixed_fee(): void
    {
        $settings = $this->settings();

        $this->assertSame(2.75, $this->fees->feeFor(100, 'creditcard', 'mada', $settings));
        $this->assertSame(2.75, $this->fees->feeFor(100, 'mada', null, $settings));
    }

    public function test_visa_uses_credit_percent_plus_fixed_fee(): void
    {
        $settings = $this->settings();

        $this->assertSame(3.50, $this->fees->feeFor(100, 'creditcard', 'visa', $settings));
        $this->assertSame(3.50, $this->fees->feeFor(100, 'creditcard', 'mastercard', $settings));
    }

    public function test_apple_pay_follows_underlying_card_company(): void
    {
        $settings = $this->settings();

        $this->assertSame(2.75, $this->fees->feeFor(100, 'applepay', 'mada', $settings));
        $this->assertSame(3.50, $this->fees->feeFor(100, 'applepay', 'visa', $settings));
        $this->assertSame(3.50, $this->fees->feeFor(100, 'apple_pay', 'mastercard', $settings));
    }

    public function test_missing_brand_defaults_to_credit_rate(): void
    {
        $settings = $this->settings();

        $this->assertSame(3.50, $this->fees->feeFor(100, 'creditcard', null, $settings));
        $this->assertSame(3.50, $this->fees->feeFor(100, 'stcpay', null, $settings));
        $this->assertSame(3.50, $this->fees->feeFor(100, null, null, $settings));
    }

    public function test_uses_built_in_defaults_when_settings_are_empty(): void
    {
        $settings = new Setting;

        $this->assertSame(1.75, $this->fees->rates($settings)['mada_percent']);
        $this->assertSame(2.50, $this->fees->rates($settings)['credit_percent']);
        $this->assertSame(1.00, $this->fees->rates($settings)['fixed_fee']);
    }

    public function test_falls_back_to_legacy_moyasar_fee_percent_for_credit(): void
    {
        $settings = new Setting;
        $settings->moyasar_fee_percent = 2.75;

        $this->assertSame(2.75, $this->fees->creditPercent($settings));
        $this->assertSame(3.75, $this->fees->feeFor(100, 'creditcard', 'visa', $settings));
    }

    private function settings(): Setting
    {
        $settings = new Setting;
        $settings->moyasar_mada_percent = 1.75;
        $settings->moyasar_credit_percent = 2.50;
        $settings->moyasar_fixed_fee = 1.00;

        return $settings;
    }
}
