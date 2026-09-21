<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportPeriodTabResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{key: string, label_ar: string, selected: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => (string) ($this->resource['key'] ?? ''),
            'label_ar' => (string) ($this->resource['label_ar'] ?? ''),
            'selected' => (bool) ($this->resource['selected'] ?? false),
        ];
    }
}
