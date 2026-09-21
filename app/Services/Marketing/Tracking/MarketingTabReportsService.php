<?php

namespace App\Services\Marketing\Tracking;

use App\Models\Contract;
use Illuminate\Support\Carbon;

class MarketingTabReportsService
{
    public function __construct(
        protected MarketingAttributionQueries $queries,
        protected MarketingChannelTrackingService $channels,
        protected MarketingTrackingOverviewService $overview,
        protected MarketingKeywordTrackingService $keywords,
    ) {}

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null, date_from: string|null, date_to: string|null, key: string}  $filter
     * @return array<string, mixed>
     */
    public function overview(array $filter, string $channel = 'all'): array
    {
        $range = $filter['range'] ?? null;
        $previous = $this->queries->previousRange($range);
        $rows = $this->scopedRows($range, $channel);
        $prevRows = $this->scopedRows($previous, $channel);
        $current = $this->totalsFromRows($rows);
        $prev = $this->totalsFromRows($prevRows);
        $customers = $this->customerSplit($range, $channel);
        $prevCustomers = $this->customerSplit($previous, $channel);
        $allRevenue = $this->allRevenue($range);
        $spend = (float) $current['spend'];
        $perRiyal = $spend > 0 ? round(((float) $current['revenue']) / $spend, 2) : null;

        $days = $range === null ? null : max(1, (int) $range[0]->diffInDays($range[1]) + 1);
        $hasPrevious = $previous !== null;

        return [
            'currency' => 'SAR',
            'currency_label_ar' => 'ريال',
            'highlights' => $this->highlights($filter, $rows, $channel),
            'comparison' => [
                'title_ar' => $days === null
                    ? 'مقارنة الفترات – الحالية مقابل السابقة'
                    : 'مقارنة الفترات – الحالية مقابل السابقة ('.$days.' يومًا)',
                'items' => [
                    $this->comparisonItem('orders', 'الطلبات', (int) $current['conversions'], (int) $prev['conversions'], $hasPrevious, false),
                    $this->comparisonItem('ad_spend', 'الصرف الإعلاني', $current['spend'], $prev['spend'], $hasPrevious, true),
                    $this->comparisonItem('attributed_revenue', 'الإيراد المُسند', $current['revenue'], $prev['revenue'], $hasPrevious, true),
                ],
            ],
            'stats' => [
                [
                    'key' => 'new_customers',
                    'label_ar' => 'عملاء جدد',
                    'value' => $customers['new'],
                    'change_percent' => $hasPrevious
                        ? $this->signedChange((float) $customers['new'], (float) $prevCustomers['new'])
                        : null,
                ],
                [
                    'key' => 'returning_customers',
                    'label_ar' => 'عملاء عائدون',
                    'value' => $customers['returning'],
                    'change_percent' => $hasPrevious
                        ? $this->signedChange((float) $customers['returning'], (float) $prevCustomers['returning'])
                        : null,
                ],
                [
                    'key' => 'marketing_cost',
                    'label_ar' => 'تكلفة التسويق',
                    'value' => $current['spend'],
                    'is_money' => true,
                ],
                [
                    'key' => 'total_revenue',
                    'label_ar' => 'إجمالي الإيراد',
                    'value' => $allRevenue,
                    'is_money' => true,
                ],
                [
                    'key' => 'revenue_per_riyal',
                    'label_ar' => 'إيراد لكل ريال تسويق',
                    'value' => $perRiyal,
                    'suffix' => 'x',
                ],
            ],
        ];
    }

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null, date_from: string|null, date_to: string|null}  $filter
     * @return array<string, mixed>
     */
    public function channelTable(array $filter, string $channel = 'all'): array
    {
        $range = $filter['range'] ?? null;
        $rows = array_map(static function (array $row) {
            unset($row['currency'], $row['label_en']);

            return $row;
        }, $this->channels->channels($range));

        $totals = $this->totalsFromRows($channel === 'all' ? $rows : $this->scopedRows($range, $channel));
        $from = $filter['date_from'] ?? null;
        $to = $filter['date_to'] ?? null;
        $rangeText = ($from && $to)
            ? Carbon::parse($from)->format('d-m-Y').' ← '.Carbon::parse($to)->format('d-m-Y')
            : 'كل الفترات';

        return [
            'currency' => 'SAR',
            'currency_label_ar' => 'ريال',
            'range_label_ar' => 'تقرير القنوات: '.$rangeText.' · '.count($rows).' صفوف',
            'rows' => $rows,
            'total' => $totals,
        ];
    }

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null, date_from: string|null, date_to: string|null, key: string}  $filter
     * @return array<string, mixed>
     */
    public function exportPayload(array $filter, string $channel = 'all'): array
    {
        return [
            'overview' => $this->overview($filter, $channel),
            'channels' => $this->channelTable($filter, $channel),
            'filter' => $filter,
            'channel' => $channel,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function totalsFromRows(array $rows): array
    {
        $spend = (float) array_sum(array_column($rows, 'spend'));
        $revenue = (float) array_sum(array_column($rows, 'revenue'));
        $leads = (int) array_sum(array_column($rows, 'leads'));
        $conversions = (int) array_sum(array_column($rows, 'conversions'));
        $profit = $revenue - $spend;
        $roas = $spend > 0 ? round($revenue / $spend, 2) : null;

        return [
            'spend' => $this->queries->money($spend),
            'revenue' => $this->queries->money($revenue),
            'roas' => $roas,
            'roas_tone' => $this->channels->roasTone($roas),
            'leads' => $leads,
            'conversions' => $conversions,
            'cac' => ($spend > 0 && $conversions > 0) ? $this->queries->money($spend / $conversions) : null,
            'profit' => $this->queries->money($profit),
        ];
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return list<array<string, mixed>>
     */
    protected function scopedRows(?array $range, string $channel): array
    {
        $rows = $this->channels->channels($range);
        if ($channel === 'all') {
            return $rows;
        }

        return array_values(array_filter($rows, static fn (array $row) => $row['source'] === $channel));
    }

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null, date_from: string|null, date_to: string|null}  $filter
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function highlights(array $filter, array $rows, string $channel): array
    {
        $overview = $this->overview->dashboard($filter);
        $keywordItems = $this->keywords->dashboard($filter)['items'] ?? [];
        $bestPage = $overview['top_pages'][0] ?? null;
        $bestKeyword = $keywordItems[0] ?? null;
        $bestCampaign = $overview['best_campaign'] ?? null;

        $bestSource = null;
        foreach ($rows as $row) {
            if ($bestSource === null || (int) $row['conversions'] > (int) $bestSource['conversions']) {
                $bestSource = $row;
            }
        }
        if ($bestSource === null && $rows !== []) {
            $bestSource = $rows[0];
        }

        return [
            [
                'key' => 'best_page',
                'title_ar' => 'أفضل صفحة',
                'label' => $bestPage['title'] ?? 'الصفحة الرئيسية',
                'badge' => null,
                'detail_ar' => $this->number((int) ($bestPage['visits'] ?? 0)).' زيارة',
            ],
            [
                'key' => 'best_keyword',
                'title_ar' => 'أفضل كلمة بحث',
                'label' => $bestKeyword['keyword'] ?? '—',
                'badge' => $bestKeyword ? (string) ($bestKeyword['current_rank'] ?? '1') : null,
                'detail_ar' => $bestKeyword
                    ? $this->number((int) ($bestKeyword['search_volume'] ?? 0)).' بحث/شهر · '.$this->number((float) ($bestKeyword['revenue'] ?? 0)).' ريال'
                    : 'لا توجد بيانات',
            ],
            [
                'key' => 'best_campaign',
                'title_ar' => 'أفضل حملة',
                'label' => $bestCampaign['campaign'] ?? '—',
                'badge' => $bestCampaign ? ['label_ar' => $bestCampaign['label_ar'], 'color' => $bestCampaign['color']] : null,
                'detail_ar' => $bestCampaign
                    ? 'ROAS x'.($bestCampaign['roas'] ?? '—').' · '.$this->number((int) ($overview['top_campaigns'][0]['orders'] ?? 0)).' طلب'
                    : 'لا توجد بيانات',
            ],
            [
                'key' => 'best_source',
                'title_ar' => 'أفضل مصدر للعملاء',
                'label' => $bestSource['label_ar'] ?? ($channel === 'all' ? '—' : $this->queries->channelMeta($channel)['label_ar']),
                'badge' => $bestSource ? ['label_ar' => $bestSource['label_ar'], 'color' => $bestSource['color']] : null,
                'detail_ar' => $bestSource
                    ? $this->number((int) $bestSource['conversions']).' عميل · '.$this->number((float) $bestSource['revenue']).' ريال إيراد'
                    : 'لا توجد بيانات',
            ],
        ];
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return array{new: int, returning: int}
     */
    protected function customerSplit(?array $range, string $channel): array
    {
        $query = $this->queries->contractAggregates($range)
            ->select('contracts.user_id')
            ->whereNotNull('contracts.user_id');
        if ($channel !== 'all') {
            $this->queries->whereSourceIn($query, [$channel]);
        }
        $userIds = $query->distinct()->pluck('user_id')->filter()->values();
        if ($userIds->isEmpty()) {
            return ['new' => 0, 'returning' => 0];
        }

        $prior = Contract::query()->notDeleted()->reachedAdminOrderStep()
            ->whereIn('user_id', $userIds->all());
        if ($range === null) {
            $returning = (int) (clone $prior)
                ->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();
        } else {
            $returning = (int) $prior
                ->where('contracts.created_at', '<', $range[0]->toDateTimeString())
                ->distinct()
                ->count('user_id');
        }

        $total = $userIds->count();

        return [
            'new' => max(0, $total - $returning),
            'returning' => $returning,
        ];
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     */
    protected function allRevenue(?array $range): int|float
    {
        $value = (float) $this->queries->revenueAggregates($range)
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
            ->value('revenue');

        return $this->queries->money($value);
    }

    protected function comparisonItem(string $key, string $label, int|float $value, int|float $previous, bool $hasPrevious, bool $isMoney): array
    {
        $item = [
            'key' => $key,
            'label_ar' => $label,
            'value' => $value,
            'previous_value' => $previous,
            'change_percent' => $hasPrevious ? $this->signedChange((float) $value, (float) $previous) : null,
        ];
        if ($isMoney) {
            $item['is_money'] = true;
        }

        return $item;
    }

    protected function signedChange(float $current, float $previous): int
    {
        return (int) round((float) $this->queries->changePercent($current, $previous));
    }

    protected function number(int|float $value): string
    {
        if (is_float($value) && abs($value - (int) round($value)) >= 0.005) {
            return number_format($value, 2);
        }

        return number_format((int) round($value));
    }
}
