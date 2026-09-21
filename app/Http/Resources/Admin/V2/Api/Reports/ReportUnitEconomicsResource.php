<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportUnitEconomicsResource extends JsonResource
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
            'customer_pays' => $this->resource['customer_pays'] ?? 0,
            'ejar_fee' => $this->resource['ejar_fee'] ?? 0,
            'moyasar_fee' => $this->resource['moyasar_fee'] ?? 0,
            'margin' => $this->resource['margin'] ?? 0,
            'margin_percent' => (int) ($this->resource['margin_percent'] ?? 0),
            'low_margin' => (bool) ($this->resource['low_margin'] ?? false),
            'highlight' => (bool) ($this->resource['highlight'] ?? $this->resource['low_margin'] ?? false),
        ];
    }
}
