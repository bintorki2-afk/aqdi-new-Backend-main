<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportPaymentMethodResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{method: string, label: string|null, value: int|float}
     */
    public function toArray(Request $request): array
    {
        return [
            'method' => (string) ($this->resource['method'] ?? ''),
            'label' => $this->resource['label'] ?? null,
            'value' => $this->resource['value'] ?? 0,
        ];
    }
}
