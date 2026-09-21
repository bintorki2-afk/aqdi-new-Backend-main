<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class SalesReportResource extends ReportJsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge($this->periodFields(), [
            'kpis' => $this->resolveItem($this->resource['kpis'] ?? [], SalesKpisResource::class),
            'by_period' => $this->resolveList($this->resource['by_period'] ?? [], ReportLabeledValueResource::class),
            'daily' => $this->resolveList($this->resource['daily'] ?? [], ReportLabeledValueResource::class),
            'revenue_by_contract_type' => $this->resolveList($this->resource['revenue_by_contract_type'] ?? [], ReportLabeledValueResource::class),
            'revenue_by_duration' => $this->resolveList($this->resource['revenue_by_duration'] ?? [], ReportLabeledValueResource::class),
            'summary' => $this->resolveItem($this->resource['summary'] ?? [], SalesSummaryResource::class),
        ]);
    }
}
