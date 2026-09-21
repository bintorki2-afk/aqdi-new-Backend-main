<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class ReportSourceSummaryResource extends ReportJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collectResolve(
                ReportSourceSummaryItemResource::class,
                $this->resource['items'] ?? []
            ),
            'refunds' => $this->resource['refunds'] ?? 0,
            'refund_rate_percent' => (int) ($this->resource['refund_rate_percent'] ?? 0),
            'net_revenue' => $this->resource['net_revenue'] ?? 0,
        ];
    }
}
