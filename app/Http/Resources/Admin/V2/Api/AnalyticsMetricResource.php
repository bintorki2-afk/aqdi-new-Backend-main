<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsMetricResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $key = (string) ($this->resource['key'] ?? '');

        $clients = $this->resource['clients'] ?? null;
        $employees = $this->resource['employees'] ?? null;
        $items = $this->resource['items'] ?? $employees ?? $clients ?? [];

        return [
            'key' => $key,
            'label_ar' => $this->resource['label_ar'] ?? null,
            'label_en' => $this->resource['label_en'] ?? null,
            'metric_label_ar' => $this->resource['metric_label_ar'] ?? null,
            'value' => $this->resource['value'],
            'type' => $this->resource['type'],
            'top_employee' => $this->resource['top_employee']
                ?? (is_array($employees) && isset($employees[0]) ? $employees[0] : null),
            'employees' => is_array($employees) ? $employees : [],
            'employees_count' => $this->resource['employees_count'] ?? (is_array($employees) ? count($employees) : 0),
            'clients' => $clients ?? $items,
            'clients_count' => $this->resource['clients_count'] ?? (is_array($clients ?? $items) ? count($clients ?? $items) : 0),
            'items' => $items,
            'items_count' => is_array($items) ? count($items) : 0,
            'meta' => $this->resource['meta'] ?? null,
        ];
    }
}
