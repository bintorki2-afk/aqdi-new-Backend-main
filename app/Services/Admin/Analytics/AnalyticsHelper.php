<?php

namespace App\Services\Admin\Analytics;

trait AnalyticsHelper
{
    public function formatFinancialRow(array $data, string $valueKey, array $labels): array
    {
        $row = [];
        foreach (['today', 'week', 'month', 'year', 'total'] as $period) {
            $item = $data[$period] ?? null;
            $value = is_array($item) ? ($item[$valueKey] ?? $item['amount'] ?? $item['count'] ?? 0) : $item;
            $percentageChange = is_array($item) && isset($item['percentage_change']) ? $item['percentage_change'] : null;
            $row[$period] = [
                'label_ar' => $labels[$period] ?? $period,
                'value' => $value,
                'percentage_change' => $percentageChange,
            ];
        }
        return $row;
    }

    protected function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
