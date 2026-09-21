<?php

namespace App\Http\Resources\Admin\V2\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportLabeledValueResource extends JsonResource
{
    public static $wrap = null;

    private const KEYS = [
        'stage',
        'date',
        'method',
        'label',
        'value',
        'revenue',
        'percent',
        'orders_count',
        'from_previous_pct',
        'is_subtotal',
        'is_total',
        'tone',
        'service',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $row = [];

        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $this->resource)) {
                $row[$key] = $this->resource[$key];
            }
        }

        if (! array_key_exists('value', $row)) {
            $row['value'] = 0;
        }

        return $row;
    }
}
