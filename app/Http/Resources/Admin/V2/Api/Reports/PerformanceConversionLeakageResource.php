<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceConversionLeakageResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{count: int, percent: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'count' => (int) ($this->resource['count'] ?? 0),
            'percent' => (int) ($this->resource['percent'] ?? 0),
        ];
    }
}
