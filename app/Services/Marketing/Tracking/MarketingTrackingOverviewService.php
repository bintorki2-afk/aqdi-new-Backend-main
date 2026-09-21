<?php

namespace App\Services\Marketing\Tracking;

use App\Models\User;
use App\Services\Seo\SearchConsole\GoogleSearchConsolePerformanceService;
use Illuminate\Support\Carbon;

class MarketingTrackingOverviewService
{
    public function __construct(
        protected MarketingAttributionQueries $queries,
        protected MarketingChannelTrackingService $channels,
        protected MarketingKeywordTrackingService $keywords,
        protected GoogleSearchConsolePerformanceService $searchConsole,
    ) {}

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null, date_from: string|null, date_to: string|null}  $filter
     * @return array<string, mixed>
     */
    public function dashboard(array $filter): array
    {
        $range = $filter['range'];
        $previous = $this->queries->previousRange($range);
        $channelRows = $this->channels->channels($range);
        $campaigns = $this->campaigns($range);
        $visits = $this->visits($filter, $previous);

        $spend = (float) array_sum(array_column($channelRows, 'spend'));
        $revenue = (float) array_sum(array_column($channelRows, 'revenue'));
        $profit = $revenue - $spend;
        $conversions = (int) array_sum(array_column($channelRows, 'conversions'));
        $roas = $spend > 0 ? round($revenue / $spend, 2) : null;
        $visitors = max(1, $visits['website']['value'] + $visits['app']['value']);
        $paying = $conversions;
        $orders = (int) $this->queries->whereSourceIn(
            $this->queries->contractBase($range),
            MarketingAttributionQueries::PAID_SOURCES
        )->count();

        $sortedByRoas = $campaigns;
        usort($sortedByRoas, static fn (array $a, array $b) => ($b['roas'] ?? -1) <=> ($a['roas'] ?? -1));
        $best = $sortedByRoas[0] ?? null;
        $weakest = $sortedByRoas === [] ? null : $sortedByRoas[count($sortedByRoas) - 1];

        $keywordItems = $this->keywords->dashboard($filter)['items'];
        $topKeywords = array_map(static fn (array $row) => [
            'keyword' => $row['keyword'],
            'rank' => $row['current_rank'],
            'status' => $row['status'],
            'status_label_ar' => $row['status_label_ar'],
            'status_label_en' => $row['status_label_en'],
        ], array_slice($keywordItems, 0, 5));

        $topCampaigns = $campaigns;
        usort($topCampaigns, static fn (array $a, array $b) => $b['orders'] <=> $a['orders']);

        return [
            'currency' => 'SAR',
            'currency_label_ar' => 'ريال',
            'summary' => [
                'roas' => $roas,
                'spend' => $this->queries->money($spend),
                'revenue' => $this->queries->money($revenue),
                'profit' => $this->queries->money($profit),
                'roas_caption_ar' => $roas === null
                    ? 'لا يوجد صرف إعلاني في الفترة'
                    : 'كل 1 ريال صرف إعلاني أرجع '.$roas.' ريال إيراد مسند',
                'roas_caption_en' => $roas === null
                    ? 'No ad spend in this period'
                    : 'For every 1 riyal spent on ads, '.$roas.' riyals of attributed revenue were returned',
            ],
            'kpis' => [
                'cac' => ($spend > 0 && $paying > 0) ? $this->queries->money($spend / $paying) : null,
                'conversion_rate' => round(($paying / $visitors) * 100, 1),
                'paying_customers' => $paying,
                'marketing_orders' => $orders,
                'app_visits' => $visits['app'],
                'website_visits' => $visits['website'],
            ],
            'chart' => array_map(static fn (array $row) => [
                'source' => $row['source'],
                'label_ar' => $row['label_ar'],
                'label_en' => $row['label_en'],
                'spend' => $row['spend'],
                'revenue' => $row['revenue'],
            ], $channelRows),
            'top_keywords' => $topKeywords,
            'top_pages' => $this->topPages($filter),
            'top_campaigns' => array_map(static fn (array $row) => [
                'campaign' => $row['campaign'],
                'source' => $row['source'],
                'label_ar' => $row['label_ar'],
                'label_en' => $row['label_en'],
                'color' => $row['color'],
                'orders' => $row['orders'],
            ], array_slice($topCampaigns, 0, 5)),
            'best_campaign' => $this->campaignHighlight($best, 'best'),
            'weakest_campaign' => $this->campaignHighlight($weakest, 'weakest'),
        ];
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array<string, mixed>>
     */
    protected function campaigns(?array $range): array
    {
        $spend = $this->queries->spendByCampaign($range);

        $campaign = $this->queries->campaignExpression();
        $source = $this->queries->sourceExpression();
        $hasCampaign = $this->queries->hasAttributionField('utm_campaign');

        $orders = collect();
        $revenue = collect();
        if ($hasCampaign) {
            $orders = $this->queries->contractAggregates($range)
                ->whereRaw("{$campaign} is not null")
                ->whereRaw("{$campaign} != ''")
                ->selectRaw("{$source} as source")
                ->selectRaw("{$campaign} as campaign")
                ->selectRaw('COUNT(*) as orders')
                ->selectRaw('SUM(CASE WHEN contracts.is_completed = 1 THEN 1 ELSE 0 END) as paid')
                ->groupByRaw($this->queries->groupBySelectPositions(2))
                ->get();

            $revenue = $this->queries->revenueAggregates($range)
                ->whereRaw("{$campaign} is not null")
                ->whereRaw("{$campaign} != ''")
                ->selectRaw("{$campaign} as campaign")
                ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
                ->groupByRaw($this->queries->groupBySelectPositions(1))
                ->pluck('revenue', 'campaign');
        }

        $items = [];
        $seen = [];
        foreach ($spend as $row) {
            $name = (string) $row->campaign_name;
            $source = (string) $row->platform;
            $spendValue = (float) $row->total_spend;
            $revenueValue = (float) ($revenue[$name] ?? 0);
            $orderRow = $orders->first(fn ($item) => (string) $item->campaign === $name);
            $key = $source.'|'.$name;
            $seen[$key] = true;
            $items[] = array_merge($this->queries->channelMeta($source), [
                'campaign' => $name,
                'spend' => $this->queries->money($spendValue),
                'revenue' => $this->queries->money($revenueValue),
                'profit' => $this->queries->money($revenueValue - $spendValue),
                'roas' => $spendValue > 0 ? round($revenueValue / $spendValue, 2) : null,
                'orders' => (int) ($orderRow->orders ?? 0),
                'conversions' => (int) ($orderRow->paid ?? 0),
            ]);
        }

        foreach ($orders as $row) {
            $source = (string) ($row->source ?: 'direct');
            $name = (string) $row->campaign;
            $key = $source.'|'.$name;
            if (isset($seen[$key])) {
                continue;
            }
            $revenueValue = (float) ($revenue[$name] ?? 0);
            $items[] = array_merge($this->queries->channelMeta($source), [
                'campaign' => $name,
                'spend' => 0,
                'revenue' => $this->queries->money($revenueValue),
                'profit' => $this->queries->money($revenueValue),
                'roas' => null,
                'orders' => (int) $row->orders,
                'conversions' => (int) $row->paid,
            ]);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>|null  $campaign
     * @return array<string, mixed>|null
     */
    protected function campaignHighlight(?array $campaign, string $kind): ?array
    {
        if ($campaign === null) {
            return null;
        }

        $profit = (float) $campaign['profit'];

        return [
            'kind' => $kind,
            'campaign' => $campaign['campaign'],
            'source' => $campaign['source'],
            'label_ar' => $campaign['label_ar'],
            'label_en' => $campaign['label_en'],
            'color' => $campaign['color'],
            'roas' => $campaign['roas'],
            'profit' => $this->queries->money($profit),
            'result_key' => $profit >= 0 ? 'profit' : 'loss',
            'result_label_ar' => $profit >= 0 ? 'ربح' : 'خسارة',
            'result_label_en' => $profit >= 0 ? 'Profit' : 'Loss',
            'result_amount' => $this->queries->money(abs($profit)),
        ];
    }

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null, date_from: string|null, date_to: string|null}  $filter
     * @param  array{0: Carbon, 1: Carbon}|null  $previous
     * @return array{app: array<string, mixed>, website: array<string, mixed>}
     */
    protected function visits(array $filter, ?array $previous): array
    {
        $website = $this->websiteVisits($filter['date_from'] ?? null, $filter['date_to'] ?? null);
        $prevWebsite = $previous
            ? $this->websiteVisits($previous[0]->toDateString(), $previous[1]->toDateString())
            : ['value' => 0, 'source' => 'none'];

        $app = $this->appVisits($filter['range']);
        $prevApp = $this->appVisits($previous);

        return [
            'website' => [
                'value' => $website['value'],
                'source' => $website['source'],
                'change_percent' => $this->queries->changePercent((float) $website['value'], (float) $prevWebsite['value']),
            ],
            'app' => [
                'value' => $app,
                'source' => 'users',
                'change_percent' => $this->queries->changePercent((float) $app, (float) $prevApp),
            ],
        ];
    }

    /**
     * @return array{value: int, source: string}
     */
    protected function websiteVisits(?string $from, ?string $to): array
    {
        if (filled($from) && filled($to)) {
            try {
                $overview = $this->searchConsole->overview($from, $to);

                return [
                    'value' => (int) ($overview['clicks'] ?? 0),
                    'source' => 'search_console',
                ];
            } catch (\Throwable) {
                // fall through to user counts
            }
        }

        return [
            'value' => $this->userCount($from && $to ? [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ] : null, [User::PLATFORM_WEBSITE]),
            'source' => 'users',
        ];
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    protected function appVisits(?array $range): int
    {
        return $this->userCount($range, [User::PLATFORM_GOOGLE_PLAY, User::PLATFORM_APPLE_STORE]);
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @param  list<string>  $platforms
     */
    protected function userCount(?array $range, array $platforms): int
    {
        if (! $this->queries->usersHavePlatform()) {
            return 0;
        }

        $query = User::query()->whereIn('platform', $platforms);
        $this->queries->applyDateRange($query, 'created_at', $range);

        return (int) $query->count();
    }

    /**
     * @param  array{date_from: string|null, date_to: string|null}  $filter
     * @return list<array<string, mixed>>
     */
    protected function topPages(array $filter): array
    {
        $from = $filter['date_from'] ?? null;
        $to = $filter['date_to'] ?? null;
        if (! filled($from) || ! filled($to)) {
            return [];
        }

        try {
            $pages = $this->searchConsole->pages($from, $to, 8);
        } catch (\Throwable) {
            return [];
        }

        $items = [];
        foreach ($pages['items'] ?? [] as $row) {
            $url = (string) ($row['page'] ?? '');
            $path = parse_url($url, PHP_URL_PATH);
            $path = is_string($path) && $path !== '' ? $path : '/';
            $type = $this->pageType($path, $url);
            $items[] = [
                'path' => $path,
                'title' => $this->pageTitle($path),
                'visits' => (int) ($row['clicks'] ?? 0),
                'type' => $type['key'],
                'type_label_ar' => $type['ar'],
                'type_label_en' => $type['en'],
            ];
        }

        return $items;
    }

    /**
     * @return array{key: string, ar: string, en: string}
     */
    protected function pageType(string $path, string $url): array
    {
        if (str_contains($url, 'blogs.') || str_contains($path, '/blog')) {
            return ['key' => 'article', 'ar' => 'مقال', 'en' => 'Article'];
        }
        if (str_contains($path, 'pricing') || str_contains($path, 'fee')) {
            return ['key' => 'service', 'ar' => 'خدمة', 'en' => 'Service'];
        }

        return ['key' => 'page', 'ar' => 'صفحة', 'en' => 'Page'];
    }

    protected function pageTitle(string $path): string
    {
        if ($path === '/' || $path === '') {
            return 'الصفحة الرئيسية';
        }

        $segment = trim($path, '/');
        $segment = str_contains($segment, '/') ? substr($segment, strrpos($segment, '/') + 1) : $segment;

        return str_replace('-', ' ', rawurldecode($segment));
    }
}
