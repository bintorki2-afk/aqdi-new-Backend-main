<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class CustomersReportResource extends ReportJsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge($this->periodFields(), [
            'kpis' => $this->resolveItem($this->resource['kpis'] ?? [], CustomersKpisResource::class),
            'segments' => $this->resolveList($this->resource['segments'] ?? [], ReportLabeledValueResource::class),
            'top_customers' => $this->resolveList($this->resource['top_customers'] ?? [], ReportTopCustomerResource::class),
        ]);
    }
}
