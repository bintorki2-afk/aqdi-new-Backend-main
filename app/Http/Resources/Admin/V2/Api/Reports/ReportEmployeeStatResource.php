<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportEmployeeStatResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{employee_id: int, label: string|null, value: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_id' => (int) ($this->resource['employee_id'] ?? 0),
            'label' => $this->resource['label'] ?? null,
            'value' => (int) ($this->resource['value'] ?? 0),
        ];
    }
}
