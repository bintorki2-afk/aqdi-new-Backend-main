<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportServiceProfitabilityResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'service' => $this->resource['service'] ?? $this->resource['label'] ?? null,
            'label' => $this->resource['label'] ?? $this->resource['service'] ?? null,
            'revenue' => $this->resource['revenue'] ?? 0,
            'ejar_fee' => $this->resource['ejar_fee'] ?? 0,
            'gateway_fee' => $this->resource['gateway_fee'] ?? 0,
            'profit' => $this->resource['profit'] ?? 0,
            'margin_percent' => (int) ($this->resource['margin_percent'] ?? 0),
        ];
    }
}
