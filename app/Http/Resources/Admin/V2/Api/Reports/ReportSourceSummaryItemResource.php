<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportSourceSummaryItemResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $row = [
            'key' => (string) ($this->resource['key'] ?? ''),
            'label' => $this->resource['label'] ?? null,
            'orders_count' => (int) ($this->resource['orders_count'] ?? 0),
            'revenue' => $this->resource['revenue'] ?? 0,
        ];

        if (array_key_exists('units_count', $this->resource)) {
            $row['units_count'] = (int) $this->resource['units_count'];
        }

        return $row;
    }
}
