<?php

namespace App\Services\Marketing\Tracking;

use Illuminate\Support\Carbon;

class MarketingChannelTrackingService
{
    public function __construct(protected MarketingAttributionQueries $queries) {}

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null}  $filter
     * @return array<string, mixed>
     */
    public function dashboard(array $filter): array
    {
        $range = $filter['range'];
        $previous = $this->queries->previousRange($range);

        return [
            'currency' => 'SAR',
            'currency_label_ar' => 'ريال',
            'funnel' => $this->funnel($range, $previous),
            'channels' => $this->channels($range),
        ];
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @param  array{0: Carbon, 1: Carbon}|null  $previous
     * @return list<array<string, mixed>>
     */
    public function funnel(?array $range, ?array $previous): array
    {
        $current = $this->funnelValues($range);
        $prev = $this->funnelValues($previous);

        $stages = [
            ['key' => 'impressions', 'ar' => 'ظهور', 'en' => 'Impressions'],
            ['key' => 'clicks', 'ar' => 'نقرات', 'en' => 'Clicks'],
            ['key' => 'leads', 'ar' => 'عملاء محتملون', 'en' => 'Leads'],
            ['key' => 'conversions', 'ar' => 'تحويلات', 'en' => 'Conversions'],
        ];

        $max = max(1, $current['impressions']);
        $items = [];
        $previousValue = null;
        foreach ($stages as $stage) {
            $value = $current[$stage['key']];
            $rateFromPrevious = $previousValue !== null && $previousValue > 0
                ? round(($value / $previousValue) * 100, 1)
                : 100.0;

            $items[] = [
                'key' => $stage['key'],
                'label_ar' => $stage['ar'],
                'label_en' => $stage['en'],
                'value' => $value,
                'previous_value' => $prev[$stage['key']],
                'change_percent' => $this->queries->changePercent((float) $value, (float) $prev[$stage['key']]),
                'rate_from_previous' => $rateFromPrevious,
                'share_percent' => round(($value / $max) * 100, 2),
            ];
            $previousValue = $value;
        }

        return $items;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array<string, mixed>>
     */
    public function channels(?array $range): array
    {
        $orderRows = $this->queries->contractAggregates($range)
            ->selectRaw($this->queries->sourceExpression().' as source')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(CASE WHEN contracts.is_completed = 1 THEN 1 ELSE 0 END) as paid')
            ->groupByRaw($this->queries->groupBySelectPositions(1))
            ->get()
            ->keyBy('source');

        $revenueRows = $this->queries->revenueAggregates($range)
            ->selectRaw($this->queries->sourceExpression('contracts').' as source')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
            ->groupByRaw($this->queries->groupBySelectPositions(1))
            ->pluck('revenue', 'source');

        $spendRows = $this->queries->spendByPlatform($range);

        $items = [];
        foreach (MarketingAttributionQueries::PAID_SOURCES as $source) {
            $paid = (int) ($orderRows[$source]->paid ?? 0);
            $spend = (float) ($spendRows[$source] ?? 0);
            $revenue = (float) ($revenueRows[$source] ?? 0);
            $profit = $revenue - $spend;
            $roas = $spend > 0 ? round($revenue / $spend, 2) : null;

            $items[] = array_merge($this->queries->channelMeta($source), [
                'spend' => $this->queries->money($spend),
                'revenue' => $this->queries->money($revenue),
                'profit' => $this->queries->money($profit),
                'roas' => $roas,
                'roas_tone' => $this->roasTone($roas),
                'leads' => (int) ($orderRows[$source]->orders ?? 0),
                'conversions' => $paid,
                'cac' => ($spend > 0 && $paid > 0) ? $this->queries->money($spend / $paid) : null,
                'currency' => 'SAR',
            ]);
        }

        return $items;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return array{impressions: int, clicks: int, leads: int, conversions: int}
     */
    protected function funnelValues(?array $range): array
    {
        $spend = $this->queries->spendFunnelTotals($range);

        $paidSources = MarketingAttributionQueries::PAID_SOURCES;
        $leads = (int) $this->queries->whereSourceIn($this->queries->contractBase($range), $paidSources)
            ->count();
        $conversions = (int) $this->queries->whereSourceIn($this->queries->contractBase($range), $paidSources)
            ->where('contracts.is_completed', 1)
            ->count();

        return [
            'impressions' => $spend['impressions'],
            'clicks' => $spend['clicks'],
            'leads' => $leads,
            'conversions' => $conversions,
        ];
    }

    public function roasTone(?float $roas): string
    {
        if ($roas === null) {
            return 'muted';
        }
        if ($roas >= 2) {
            return 'good';
        }
        if ($roas >= 1) {
            return 'ok';
        }

        return 'bad';
    }
}
