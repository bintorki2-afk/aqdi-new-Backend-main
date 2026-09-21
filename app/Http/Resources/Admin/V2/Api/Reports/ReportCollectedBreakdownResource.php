<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportCollectedBreakdownResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'documentation' => $this->resource['documentation'] ?? 0,
            'meter_transfers' => $this->resource['meter_transfers'] ?? 0,
            'contracts_count' => (int) ($this->resource['contracts_count'] ?? 0),
            'meter_units' => (int) ($this->resource['meter_units'] ?? 0),
        ];
    }
}
