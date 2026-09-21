<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;

class OrdersReportResource extends ReportJsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge($this->periodFields(), [
            'kpis' => $this->resolveItem($this->resource['kpis'] ?? [], OrdersKpisResource::class),
            'by_employee' => $this->resolveList($this->resource['by_employee'] ?? [], ReportEmployeeStatResource::class),
            'by_contract_type' => $this->resolveList($this->resource['by_contract_type'] ?? [], ReportLabeledValueResource::class),
            'by_stage' => $this->resolveList($this->resource['by_stage'] ?? [], ReportLabeledValueResource::class),
        ]);
    }
}
