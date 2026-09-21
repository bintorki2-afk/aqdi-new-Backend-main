<?php

namespace App\Services\Admin;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class MoyasarFeeService
{
    public const DEFAULT_MADA_PERCENT = 1.75;

    public const DEFAULT_CREDIT_PERCENT = 2.50;

    public const DEFAULT_FIXED_FEE = 1.00;

    /**
     * @return array{mada_percent: float, credit_percent: float, fixed_fee: float}
     */
    public function rates(?Setting $settings = null): array
    {
        $settings ??= Setting::query()->first();

        $credit = $settings?->moyasar_credit_percent;
        if ($credit === null && $settings?->moyasar_fee_percent !== null) {
            $credit = $settings->moyasar_fee_percent;
        }

        return [
            'mada_percent' => $settings?->moyasar_mada_percent !== null
                ? (float) $settings->moyasar_mada_percent
                : self::DEFAULT_MADA_PERCENT,
            'credit_percent' => $credit !== null
                ? (float) $credit
                : self::DEFAULT_CREDIT_PERCENT,
            'fixed_fee' => $settings?->moyasar_fixed_fee !== null
                ? (float) $settings->moyasar_fixed_fee
                : self::DEFAULT_FIXED_FEE,
        ];
    }

    public function creditPercent(?Setting $settings = null): float
    {
        return $this->rates($settings)['credit_percent'];
    }

    public function feeFor(float $amount, ?string $method, ?string $brand, ?Setting $settings = null): float
    {
        $rates = $this->rates($settings);
        $percent = $this->isMada($method, $brand)
            ? $rates['mada_percent']
            : $rates['credit_percent'];

        return round(($amount * $percent / 100) + $rates['fixed_fee'], 2);
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    public function totalForPeriod(?array $range, ?Setting $settings = null): float
    {
        $settings ??= Setting::query()->first();
        $query = Payment::query()->successful();
        $this->applyPaymentDateRange($query, $range);

        $total = 0.0;
        foreach ($query->get(['amount', 'payment_method', 'payment_brand']) as $payment) {
            $total += $this->feeFor(
                (float) $payment->amount,
                $payment->payment_method,
                $payment->payment_brand,
                $settings
            );
        }

        return round($total, 2);
    }

    public function isMada(?string $method, ?string $brand): bool
    {
        $brand = strtolower(trim((string) $brand));
        $method = strtolower(trim((string) $method));

        if ($brand === 'mada' || str_contains($brand, 'mada')) {
            return true;
        }

        if (in_array($method, ['mada', 'mada_card'], true) || str_contains($method, 'mada')) {
            return true;
        }

        return false;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    private function applyPaymentDateRange($query, ?array $range): void
    {
        if ($range === null) {
            return;
        }

        $query->whereBetween('payment_date', [$range[0]->toDateString(), $range[1]->toDateString()]);
    }
}
