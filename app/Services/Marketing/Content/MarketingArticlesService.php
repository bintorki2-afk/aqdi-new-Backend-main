<?php

namespace App\Services\Marketing\Content;

use App\Models\Blog;
use App\Services\Marketing\Tracking\MarketingAttributionQueries;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class MarketingArticlesService
{
    public const STATUSES = [
        'published' => 'منشور',
        'scheduled' => 'مجدول',
        'draft' => 'مسودة',
        'archived' => 'مؤرشف',
    ];

    public function __construct(protected MarketingAttributionQueries $queries) {}

    /**
     * @param  array{range: array{0: Carbon, 1: Carbon}|null}  $filter
     * @return array<string, mixed>
     */
    public function dashboard(array $filter, ?string $category = null, ?string $status = null): array
    {
        $range = $filter['range'] ?? null;
        $blogs = Blog::query()->orderByDesc('updated_at')->get();
        $metrics = $this->metricsBySlug($blogs->pluck('slug')->filter()->all(), $range);

        $items = [];
        $viewsTotal = 0;
        $revenueTotal = 0;
        foreach ($blogs as $blog) {
            $row = $this->articleRow($blog, $metrics[$blog->slug] ?? ['leads' => 0, 'revenue' => 0]);
            $viewsTotal += $row['views'];
            $revenueTotal += $row['attributed_revenue'];
            if ($category && $category !== 'all' && ($blog->category ?? '') !== $category) {
                continue;
            }
            if ($status && $blog->status !== $status) {
                continue;
            }
            $items[] = $row;
        }

        $queueQuery = Blog::query()->whereIn('status', ['draft', 'scheduled']);
        if ($category && $category !== 'all') {
            $queueQuery->where('category', $category);
        }
        $editorialQueue = $queueQuery
            ->orderByRaw('CASE WHEN publish_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('publish_at')
            ->get()
            ->map(fn (Blog $blog) => [
                'id' => $blog->id,
                'title' => $blog->title,
                'category_label_ar' => $this->categoryLabel($blog),
                'scheduled_at' => $blog->status === 'scheduled' ? $this->dateOnly($blog->publish_at) : null,
                'status' => $blog->status,
                'status_label_ar' => self::STATUSES[$blog->status] ?? $blog->status,
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'total' => $blogs->count(),
                'published' => $blogs->where('status', 'published')->count(),
                'scheduled' => $blogs->where('status', 'scheduled')->count(),
                'archived' => $blogs->where('status', 'archived')->count(),
                'views' => $viewsTotal,
                'attributed_revenue' => $this->queries->money((float) $revenueTotal),
            ],
            'categories' => $this->categories($blogs),
            'items' => $items,
            'editorial_queue' => $editorialQueue,
        ];
    }

    /**
     * @param  list<string>  $slugs
     * @param  array{0: Carbon, 1: Carbon}|null  $range
     * @return array<string, array{leads: int, revenue: int|float}>
     */
    protected function metricsBySlug(array $slugs, ?array $range): array
    {
        if ($slugs === [] || (! $this->queries->hasAttributionField('utm_content') && ! $this->queries->hasAttributionField('utm_campaign'))) {
            return [];
        }

        $match = $this->slugMatchExpression();
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));

        $leads = $this->queries->contractAggregates($range)
            ->whereRaw("{$match} in ({$placeholders})", $slugs)
            ->selectRaw("{$match} as slug")
            ->selectRaw('COUNT(*) as leads')
            ->groupByRaw($this->queries->groupBySelectPositions(1))
            ->pluck('leads', 'slug');

        $revenue = $this->queries->revenueAggregates($range)
            ->whereRaw("{$match} in ({$placeholders})", $slugs)
            ->selectRaw("{$match} as slug")
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
            ->groupByRaw($this->queries->groupBySelectPositions(1))
            ->pluck('revenue', 'slug');

        $out = [];
        foreach ($slugs as $slug) {
            $out[$slug] = [
                'leads' => (int) ($leads[$slug] ?? 0),
                'revenue' => $this->queries->money((float) ($revenue[$slug] ?? 0)),
            ];
        }

        return $out;
    }

    protected function slugMatchExpression(): string
    {
        $content = $this->queries->contentExpression();
        $campaign = $this->queries->campaignExpression();

        if ($content === 'NULL') {
            return $campaign;
        }
        if ($campaign === 'NULL') {
            return $content;
        }

        return "COALESCE({$content}, {$campaign})";
    }

    /**
     * @param  array{leads: int, revenue: int|float}  $metrics
     * @return array<string, mixed>
     */
    protected function articleRow(Blog $blog, array $metrics): array
    {
        $isScheduled = $blog->status === 'scheduled';

        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'category_label_ar' => $this->categoryLabel($blog),
            'author' => $blog->author,
            'status' => $blog->status,
            'status_label_ar' => self::STATUSES[$blog->status] ?? $blog->status,
            'published_at' => $blog->status === 'published' ? $this->dateOnly($blog->publish_at ?? $blog->created_at) : null,
            'scheduled_at' => $isScheduled ? $this->dateOnly($blog->publish_at) : null,
            'words' => $this->wordCount($blog->description),
            'views' => Schema::hasColumn('blogs', 'views_count') ? (int) ($blog->views_count ?? 0) : 0,
            'leads' => (int) ($metrics['leads'] ?? 0),
            'attributed_revenue' => $metrics['revenue'] ?? 0,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Blog>  $blogs
     * @return list<array{key: string, label_ar: string}>
     */
    protected function categories($blogs): array
    {
        $items = [['key' => 'all', 'label_ar' => 'الكل']];
        $seen = [];
        foreach ($blogs as $blog) {
            $key = (string) ($blog->category ?? '');
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $items[] = [
                'key' => $key,
                'label_ar' => $this->categoryLabel($blog),
            ];
        }

        return $items;
    }

    protected function categoryLabel(Blog $blog): ?string
    {
        $label = $blog->category_label_ar ?: $blog->category;

        return $label !== null && $label !== '' ? $label : null;
    }

    protected function wordCount(?string $html): int
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)) ?? '');

        return $plain === '' ? 0 : count(preg_split('/\s+/u', $plain) ?: []);
    }

    protected function dateOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
