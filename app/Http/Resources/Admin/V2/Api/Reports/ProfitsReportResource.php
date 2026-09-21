<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class ProfitsReportResource extends ReportJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge($this->periodFields(), [
            'kpis' => $this->resolveItem(
                $this->resource['kpis'] ?? [],
                ProfitsKpisResource::class
            ),
            'collected_breakdown' => $this->resolveItem(
                $this->resource['collected_breakdown'] ?? [],
                ReportCollectedBreakdownResource::class
            ),
            'service_revenue' => $this->resolveList(
                $this->resource['service_revenue'] ?? [],
                ReportServiceRevenueResource::class
            ),
            'service_profitability' => $this->resolveList(
                $this->resource['service_profitability'] ?? [],
                ReportServiceProfitabilityResource::class
            ),
            'unit_economics' => $this->resolveList(
                $this->resource['unit_economics'] ?? [],
                ReportUnitEconomicsResource::class
            ),
            'source_summary' => $this->resolveItem(
                $this->resource['source_summary'] ?? [],
                ReportSourceSummaryResource::class
            ),
            'pnl' => $this->resolveList(
                $this->resource['pnl'] ?? [],
                ReportLabeledValueResource::class
            ),
        ]);
    }
}
