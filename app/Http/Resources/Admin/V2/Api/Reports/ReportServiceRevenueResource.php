<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportServiceRevenueResource extends JsonResource
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
            'orders_count' => (int) ($this->resource['orders_count'] ?? 0),
            'revenue_share_percent' => (int) ($this->resource['revenue_share_percent'] ?? 0),
        ];
    }
}
