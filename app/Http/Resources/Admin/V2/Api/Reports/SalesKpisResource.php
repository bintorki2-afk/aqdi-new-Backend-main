<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesKpisResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_sales' => $this->resource['total_sales'] ?? 0,
            'payments_count' => (int) ($this->resource['payments_count'] ?? 0),
            'avg_order_value' => $this->resource['avg_order_value'] ?? 0,
            'discounts_used' => $this->resource['discounts_used'] ?? 0,
            'refunds' => $this->resource['refunds'] ?? 0,
            'net_revenue' => $this->resource['net_revenue'] ?? 0,
        ];
    }
}
