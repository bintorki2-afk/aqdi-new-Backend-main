<?php

namespace App\Services\Marketing\Tracking;

use App\Services\Seo\SearchConsole\GoogleSearchConsoleClient;
use App\Services\Seo\SearchConsole\GoogleSearchConsoleSiteService;

class MarketingKeywordTrackingService
{
    public function __construct(
        protected MarketingAttributionQueries $queries,
        protected GoogleSearchConsoleClient $console,
        protected GoogleSearchConsoleSiteService $sites,
    ) {}

    /**
     * @param  array{range: array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}|null, date_from: string|null, date_to: string|null}  $filter
     * @return array<string, mixed>
     */
    public function dashboard(array $filter): array
    {
        $range = $filter['range'] ?? null;
        $from = $filter['date_from'] ?? ($range[0] ?? null)?->toDateString();
        $to = $filter['date_to'] ?? ($range[1] ?? null)?->toDateString();
        $previous = $this->queries->previousRange($range);

        $currentSearch = $this->searchConsoleByQuery($from, $to);
        $previousSearch = $previous
            ? $this->searchConsoleByQuery($previous[0]->toDateString(), $previous[1]->toDateString())
            : [];

        $revenueByKeyword = $this->revenueByKeyword($range);
        $adsByKeyword = $this->adsByKeyword($range);

        $keywords = array_unique(array_filter([
            ...array_keys($currentSearch),
            ...array_keys($revenueByKeyword),
            ...array_keys($adsByKeyword),
        ]));

        $items = [];
        foreach ($keywords as $keyword) {
            $search = $currentSearch[$keyword] ?? null;
            $prev = $previousSearch[$keyword] ?? null;
            $rank = $search !== null ? (int) max(1, round((float) $search['position'])) : null;
            $previousRank = $prev !== null ? (int) max(1, round((float) $prev['position'])) : null;
            $status = $this->rankStatus($rank, $previousRank);
            $impressions = (int) ($search['impressions'] ?? $adsByKeyword[$keyword]['impressions'] ?? 0);
            $ctr = (float) ($search['ctr'] ?? 0);
            $competition = $this->competition($ctr, $impressions);
            $pagePath = $this->pagePath($search['page'] ?? null);

            $items[] = [
                'keyword' => $keyword,
                'page_path' => $pagePath,
                'current_rank' => $rank,
                'previous_rank' => $previousRank,
                'rank_tone' => $this->rankTone($rank),
                'search_volume' => $impressions,
                'impressions' => $impressions,
                'clicks' => (int) ($search['clicks'] ?? $adsByKeyword[$keyword]['clicks'] ?? 0),
                'competition' => $competition['key'],
                'competition_label_ar' => $competition['ar'],
                'competition_label_en' => $competition['en'],
                'status' => $status['key'],
                'status_label_ar' => $status['ar'],
                'status_label_en' => $status['en'],
                'revenue' => $this->queries->money((float) ($revenueByKeyword[$keyword]['revenue'] ?? 0)),
                'orders' => (int) ($revenueByKeyword[$keyword]['orders'] ?? 0),
                'currency' => 'SAR',
            ];
        }

        usort($items, static function (array $a, array $b) {
            $aRank = $a['current_rank'] ?? PHP_INT_MAX;
            $bRank = $b['current_rank'] ?? PHP_INT_MAX;
            if ($aRank !== $bRank) {
                return $aRank <=> $bRank;
            }

            return $b['revenue'] <=> $a['revenue'];
        });

        $items = array_slice(array_values($items), 0, 25);
        $increased = count(array_filter($items, fn (array $row) => $row['status'] === 'increased'));
        $decreased = count(array_filter($items, fn (array $row) => $row['status'] === 'decreased'));
        $ranked = array_values(array_filter($items, fn (array $row) => $row['current_rank'] !== null));
        $avgRank = $ranked === []
            ? null
            : round(array_sum(array_column($ranked, 'current_rank')) / count($ranked), 1);

        return [
            'currency' => 'SAR',
            'currency_label_ar' => 'ريال',
            'summary' => [
                'organic_revenue' => $this->queries->money((float) array_sum(array_column($items, 'revenue'))),
                'organic_clicks' => (int) array_sum(array_column($items, 'clicks')),
                'decreased' => $decreased,
                'increased' => $increased,
                'average_rank' => $avgRank,
                'target_keywords' => count($items),
            ],
            'items' => $items,
        ];
    }

    /**
     * @param  array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}|null  $range
     * @return array<string, array{revenue: float, orders: int}>
     */
    protected function revenueByKeyword(?array $range): array
    {
        $rows = $this->queries->revenueAggregates($range);
        $term = $this->queries->termExpression();
        if (! $this->queries->hasAttributionField('utm_term')) {
            return [];
        }

        $rows = $rows
            ->whereRaw("{$term} is not null")
            ->whereRaw("{$term} != ''")
            ->selectRaw("{$term} as keyword")
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
            ->selectRaw('COUNT(DISTINCT contracts.id) as orders')
            ->groupByRaw($this->queries->groupBySelectPositions(1))
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->keyword] = [
                'revenue' => (float) $row->revenue,
                'orders' => (int) $row->orders,
            ];
        }

        return $map;
    }

    /**
     * @param  array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}|null  $range
     * @return array<string, array{impressions: int, clicks: int}>
     */
    protected function adsByKeyword(?array $range): array
    {
        $rows = $this->queries->spendByKeyword($range);

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->keyword] = [
                'impressions' => (int) $row->impressions,
                'clicks' => (int) $row->clicks,
            ];
        }

        return $map;
    }

    /**
     * @return array<string, array{position: float, clicks: int, impressions: int, ctr: float, page: string|null}>
     */
    protected function searchConsoleByQuery(?string $from, ?string $to): array
    {
        if (! filled($from) || ! filled($to)) {
            return [];
        }

        try {
            $siteUrl = $this->sites->siteUrl();
            $payload = $this->console->querySearchAnalytics($siteUrl, [
                'startDate' => $from,
                'endDate' => $to,
                'dimensions' => ['query', 'page'],
                'rowLimit' => 250,
                'dataState' => 'all',
            ]);
        } catch (\Throwable) {
            return [];
        }

        $map = [];
        foreach ($payload['rows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $query = (string) ($row['keys'][0] ?? '');
            if ($query === '') {
                continue;
            }
            $clicks = (int) ($row['clicks'] ?? 0);
            $existing = $map[$query] ?? null;
            if ($existing !== null && $clicks < $existing['clicks']) {
                continue;
            }
            $map[$query] = [
                'position' => (float) ($row['position'] ?? 0),
                'clicks' => $clicks,
                'impressions' => (int) ($row['impressions'] ?? 0),
                'ctr' => (float) ($row['ctr'] ?? 0),
                'page' => isset($row['keys'][1]) ? (string) $row['keys'][1] : null,
            ];
        }

        return $map;
    }

    /**
     * @return array{key: string, ar: string, en: string}
     */
    protected function rankStatus(?int $current, ?int $previous): array
    {
        if ($current === null || $previous === null || $current === $previous) {
            return ['key' => 'stable', 'ar' => 'ثابتة', 'en' => 'Stable'];
        }

        if ($current < $previous) {
            return ['key' => 'increased', 'ar' => 'ارتفعت', 'en' => 'Increased'];
        }

        return ['key' => 'decreased', 'ar' => 'انخفضت', 'en' => 'Decreased'];
    }

    /**
     * @return array{key: string, ar: string, en: string}
     */
    protected function competition(float $ctr, int $impressions): array
    {
        $high = $ctr > 0 ? $ctr < 0.03 : $impressions >= 8000;
        $medium = $ctr > 0 ? $ctr < 0.08 : $impressions >= 2000;

        if ($high) {
            return ['key' => 'high', 'ar' => 'عالية', 'en' => 'High'];
        }
        if ($medium) {
            return ['key' => 'medium', 'ar' => 'متوسطة', 'en' => 'Medium'];
        }

        return ['key' => 'low', 'ar' => 'منخفضة', 'en' => 'Low'];
    }

    protected function rankTone(?int $rank): string
    {
        if ($rank === null) {
            return 'muted';
        }
        if ($rank <= 3) {
            return 'good';
        }
        if ($rank <= 10) {
            return 'warn';
        }

        return 'muted';
    }

    protected function pagePath(?string $pageUrl): string
    {
        if (! filled($pageUrl)) {
            return '/';
        }

        $path = parse_url($pageUrl, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }
}
