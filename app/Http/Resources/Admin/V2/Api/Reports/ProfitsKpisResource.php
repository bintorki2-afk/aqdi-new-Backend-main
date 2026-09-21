<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfitsKpisResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'customer_income' => $this->resource['customer_income'] ?? 0,
            'gross_profit' => $this->resource['gross_profit'] ?? 0,
            'net_profit' => $this->resource['net_profit'] ?? 0,
            'margin_percent' => (int) ($this->resource['margin_percent'] ?? 0),
            'profit_per_order' => $this->resource['profit_per_order'] ?? 0,
            'ad_spend' => $this->resource['ad_spend'] ?? 0,
            'ejar_platform_fees' => $this->resource['ejar_platform_fees'] ?? 0,
            'gateway_fee' => $this->resource['gateway_fee'] ?? 0,
            'messaging_cost' => $this->resource['messaging_cost'] ?? 0,
            'salaries_included' => (bool) ($this->resource['salaries_included'] ?? false),
            'paid_contracts_count' => (int) ($this->resource['paid_contracts_count'] ?? 0),
            'operating_profit_per_contract' => $this->resource['operating_profit_per_contract'] ?? 0,
            'monthly_break_even_contracts' => (int) ($this->resource['monthly_break_even_contracts'] ?? 0),
            'cac' => $this->resource['cac'] ?? 0,
            'proration_days' => (int) ($this->resource['proration_days'] ?? 0),
            'proration_month_days' => (int) ($this->resource['proration_month_days'] ?? 30),
        ];
    }
}
