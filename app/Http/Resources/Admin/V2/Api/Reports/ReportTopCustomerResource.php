<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportTopCustomerResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'customer_id' => (int) ($this->resource['customer_id'] ?? 0),
            'name' => $this->resource['name'] ?? null,
            'mobile' => $this->resource['mobile'] ?? null,
            'contracts_count' => (int) ($this->resource['contracts_count'] ?? 0),
            'paid_count' => (int) ($this->resource['paid_count'] ?? 0),
            'total_spending' => $this->resource['total_spending'] ?? 0,
        ];
    }
}
