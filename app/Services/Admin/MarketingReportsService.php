<?php

namespace App\Services\Admin;

use App\Services\Marketing\AdSpendSyncService;
use App\Services\Marketing\Tracking\MarketingAttributionQueries;
use App\Support\Concerns\ResolvesReportPeriod;
use App\Support\Marketing\UtmAttribution;
use Illuminate\Support\Carbon;

class MarketingReportsService
{
    use ResolvesReportPeriod;

    public function __construct(protected MarketingAttributionQueries $queries) {}

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null}  $filter
     * @return array<string, mixed>
     */
    public function dashboard(array $filter): array
    {
        $range = $filter['range'];
        $bySource = $this->bySource($range);

        return [
            'accounts' => app(AdSpendSyncService::class)->credentialStatus(),
            'by_source' => $bySource,
            'orders_by_source' => array_map(static fn (array $row) => [
                'source' => $row['source'],
                'label' => $row['label'],
                'orders' => $row['orders'],
                'paid' => $row['paid'],
                'revenue' => $row['revenue'],
            ], $bySource),
            'top_keywords' => $this->topKeywords($range),
            'weakest_campaigns' => $this->weakestCampaigns($range),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function utmTemplate(): array
    {
        $sources = [];
        foreach (config('ads.utm.sources', []) as $key => $labels) {
            $sources[] = [
                'source' => $key,
                'label_ar' => $labels['ar'] ?? $key,
                'label_en' => $labels['en'] ?? $key,
            ];
        }

        $exampleQuery = UtmAttribution::buildQuery(
            'google',
            'search-lease',
            'عقد إيجار إلكتروني',
            'adgroup-1'
        );

        return [
            'template' => '?utm_source={source}&utm_medium=cpc&utm_campaign={campaign}&utm_term={keyword}&utm_content={adset}',
            'example' => url('/').'/?'.$exampleQuery,
            'whatsapp_example' => 'https://wa.me/966000000000?text='.rawurlencode('مرحبا').'&'.$exampleQuery,
            'click_ids' => array_keys(config('ads.utm.click_ids', [])),
            'sources' => $sources,
            'accounts' => app(AdSpendSyncService::class)->credentialStatus(),
        ];
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array<string, mixed>>
     */
    private function bySource(?array $range): array
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

        $sources = array_keys(config('ads.utm.sources', []));
        $seen = array_unique([
            ...$sources,
            ...$orderRows->keys()->all(),
            ...$revenueRows->keys()->all(),
            ...$spendRows->keys()->all(),
        ]);

        $items = [];
        foreach ($seen as $source) {
            $source = (string) $source;
            $orders = (int) ($orderRows[$source]->orders ?? 0);
            $paid = (int) ($orderRows[$source]->paid ?? 0);
            $revenue = $this->queries->money((float) ($revenueRows[$source] ?? 0));
            $spend = $this->queries->money((float) ($spendRows[$source] ?? 0));
            $conversion = $orders > 0 ? (int) round(($paid / $orders) * 100) : 0;

            $items[] = [
                'source' => $source,
                'label' => UtmAttribution::sourceLabel($source),
                'orders' => $orders,
                'paid' => $paid,
                'revenue' => $revenue,
                'spend' => $spend,
                'cac' => ($spend > 0 && $paid > 0) ? $this->queries->money($spend / $paid) : null,
                'conversion_percent' => $conversion,
            ];
        }

        usort($items, static fn (array $a, array $b) => $b['orders'] <=> $a['orders']);

        return array_values($items);
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array{keyword: string, revenue: int|float, orders: int}>
     */
    private function topKeywords(?array $range): array
    {
        if (! $this->queries->hasAttributionField('utm_term')) {
            return [];
        }

        $term = $this->queries->termExpression();

        return $this->queries->revenueAggregates($range)
            ->whereRaw("{$term} is not null")
            ->whereRaw("{$term} != ''")
            ->selectRaw("{$term} as keyword")
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
            ->selectRaw('COUNT(DISTINCT contracts.id) as orders')
            ->groupByRaw($this->queries->groupBySelectPositions(1))
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'keyword' => (string) $row->keyword,
                'revenue' => $this->queries->money((float) $row->revenue),
                'orders' => (int) $row->orders,
            ])
            ->all();
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array<string, mixed>>
     */
    private function weakestCampaigns(?array $range): array
    {
        $spend = $this->queries->spendByCampaign($range);

        $campaign = $this->queries->campaignExpression();
        $revenue = collect();
        if ($this->queries->hasAttributionField('utm_campaign')) {
            $revenue = $this->queries->revenueAggregates($range)
                ->whereRaw("{$campaign} is not null")
                ->whereRaw("{$campaign} != ''")
                ->selectRaw("{$campaign} as campaign_name")
                ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
                ->groupByRaw($this->queries->groupBySelectPositions(1))
                ->pluck('revenue', 'campaign_name');
        }

        $campaigns = [];
        foreach ($spend as $row) {
            $name = (string) $row->campaign_name;
            $spendValue = (float) $row->total_spend;
            if ($spendValue <= 0) {
                continue;
            }
            $revenueValue = (float) ($revenue[$name] ?? 0);
            $profit = $revenueValue - $spendValue;
            $roas = $spendValue > 0 ? round($revenueValue / $spendValue, 2) : null;

            $campaigns[] = [
                'platform' => (string) $row->platform,
                'platform_label' => UtmAttribution::sourceLabel((string) $row->platform),
                'campaign' => $name,
                'spend' => $this->queries->money($spendValue),
                'revenue' => $this->queries->money($revenueValue),
                'profit' => $this->queries->money($profit),
                'roas' => $roas,
            ];
        }

        usort($campaigns, static function (array $a, array $b) {
            $aRoas = $a['roas'] ?? PHP_INT_MAX;
            $bRoas = $b['roas'] ?? PHP_INT_MAX;

            return $aRoas <=> $bRoas;
        });

        return array_slice(array_values($campaigns), 0, 10);
    }
}
