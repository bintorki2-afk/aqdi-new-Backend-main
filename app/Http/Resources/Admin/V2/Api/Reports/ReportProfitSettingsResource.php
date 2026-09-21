<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use App\Models\Setting;
use Illuminate\Http\Request;

class ReportProfitSettingsResource extends ReportJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $settings = $this->settings();
        $moyasar = is_array($this->resource['moyasar'] ?? null) ? $this->resource['moyasar'] : [];
        $creditPercent = $moyasar['credit_percent'] ?? null;

        $payload = [
            'moyasar_fee_percent' => $creditPercent,
            'moyasar_mada_percent' => $moyasar['mada_percent'] ?? null,
            'moyasar_credit_percent' => $creditPercent,
            'moyasar_fixed_fee' => $moyasar['fixed_fee'] ?? null,
            'operating_budget' => $this->nullableFloat($settings?->operating_budget),
            'marketing_budget' => $this->nullableFloat($settings?->marketing_budget),
            'meter_transfer_fee' => $this->resource['meter_transfer_fee'] ?? 0,
            'proration_month_days' => (int) ($this->resource['proration_month_days'] ?? 30),
        ];

        if (($this->resource['include_salaries'] ?? false) === true) {
            $payload['monthly_salaries'] = $this->nullableFloat($settings?->monthly_salaries);
        }

        return $payload;
    }

    private function settings(): ?Setting
    {
        $settings = $this->resource['settings'] ?? null;

        return $settings instanceof Setting ? $settings : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value !== null ? (float) $value : null;
    }
}
