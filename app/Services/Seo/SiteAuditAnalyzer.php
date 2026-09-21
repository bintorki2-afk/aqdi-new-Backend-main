<?php

namespace App\Services\Seo;

use App\Support\SeoCrawlIssueType;

class SiteAuditAnalyzer
{
    /**
     * @param  list<array<string, mixed>>  $pages
     * @return array{issues: list<array<string, mixed>>, inbound: array<string, int>}
     */
    public function analyze(array $pages, int $slowPageMs, int $weakInbound): array
    {
        $inbound = $this->inboundCounts($pages);
        $issues = [];

        $titleGroups = [];
        $descriptionGroups = [];
        foreach ($pages as $page) {
            if (! $this->isAuditableHtml($page)) {
                continue;
            }
            $title = $this->text($page['title'] ?? null);
            if ($title !== null) {
                $titleGroups[$title][] = $page;
            }
            $description = $this->text($page['meta_description'] ?? null);
            if ($description !== null) {
                $descriptionGroups[$description][] = $page;
            }
        }

        foreach ($pages as $page) {
            $status = (int) ($page['status_code'] ?? 0);

            if ($status === 404) {
                $issues[] = $this->issue($page, SeoCrawlIssueType::Page404);
            }
        }

        $seenBrokenTargets = [];
        foreach ($pages as $page) {
            foreach ($page['broken_links'] ?? [] as $broken) {
                $target = (string) ($broken['url'] ?? '');
                if ($target === '' || isset($seenBrokenTargets[$target])) {
                    continue;
                }
                $seenBrokenTargets[$target] = true;
                $issues[] = $this->issue($page, SeoCrawlIssueType::BrokenLink, [
                    'url' => $target,
                    'target_path' => $broken['path'] ?? null,
                    'status_code' => $broken['status_code'] ?? 404,
                ]);
            }
        }

        foreach ($pages as $page) {
            if (! $this->isAuditableHtml($page)) {
                continue;
            }

            if (empty($page['is_indexable'])) {
                $issues[] = $this->issue($page, SeoCrawlIssueType::NonIndexable);
            }

            $title = $this->text($page['title'] ?? null);
            if ($title === null) {
                $issues[] = $this->issue($page, SeoCrawlIssueType::MissingTitle);
            } elseif (count($titleGroups[$title] ?? []) > 1) {
                $other = $this->otherPath($titleGroups[$title], $page);
                $issues[] = $this->issue($page, SeoCrawlIssueType::DuplicateTitle, [
                    'other_path' => $other,
                ], ['path' => $other ?? '']);
            }

            $description = $this->text($page['meta_description'] ?? null);
            if ($description === null) {
                $issues[] = $this->issue($page, SeoCrawlIssueType::MissingDescription);
            } elseif (count($descriptionGroups[$description] ?? []) > 1) {
                $issues[] = $this->issue($page, SeoCrawlIssueType::DuplicateDescription);
            }

            if ((int) ($page['h1_count'] ?? 0) < 1) {
                $issues[] = $this->issue($page, SeoCrawlIssueType::MissingH1);
            }

            $loadMs = (int) ($page['load_time_ms'] ?? 0);
            if ($loadMs >= $slowPageMs) {
                $seconds = number_format($loadMs / 1000, 1);
                $issues[] = $this->issue($page, SeoCrawlIssueType::SlowPage, [
                    'load_time_ms' => $loadMs,
                    'seconds' => $seconds,
                ], ['seconds' => $seconds]);
            }

            $missingAlt = (int) ($page['images_missing_alt'] ?? 0);
            if ($missingAlt > 0) {
                $issues[] = $this->issue($page, SeoCrawlIssueType::ImagesMissingAlt, [
                    'count' => $missingAlt,
                ], ['count' => $missingAlt]);
            }

            $path = (string) ($page['path'] ?? '/');
            $urlKey = $this->pageKey($page);
            $inboundCount = $inbound[$urlKey] ?? 0;
            if ($path !== '/' && $inboundCount < $weakInbound) {
                $issues[] = $this->issue($page, SeoCrawlIssueType::WeakInternalLinks, [
                    'inbound' => $inboundCount,
                ]);
            }
        }

        return [
            'issues' => $issues,
            'inbound' => $inbound,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @param  list<array<string, mixed>>  $issues
     * @param  array<string, int>  $inbound
     * @return array<string, mixed>
     */
    public function summarize(array $pages, array $issues, array $inbound): array
    {
        $indexed = 0;
        $issueKeysByPage = [];
        foreach ($issues as $issue) {
            $issueKeysByPage[$issue['page_key']][] = $issue['type'];
        }

        $healthy = 0;
        foreach ($pages as $page) {
            if (! $this->isIndexed($page)) {
                continue;
            }
            $indexed++;
            $key = $this->pageKey($page);
            if (empty($issueKeysByPage[$key])) {
                $healthy++;
            }
        }

        $counts = [];
        foreach (SeoCrawlIssueType::cases() as $type) {
            $counts[$type->value] = 0;
        }
        foreach ($issues as $issue) {
            $counts[$issue['type']] = ($counts[$issue['type']] ?? 0) + 1;
        }

        $onPagePages = [];
        foreach ($issues as $issue) {
            if (in_array($issue['type'], SeoCrawlIssueType::onPageTypes(), true)) {
                $onPagePages[$issue['page_key']] = true;
            }
        }

        return [
            'indexed_pages' => $indexed,
            'healthy_pages' => $healthy,
            'broken_pages' => ($counts[SeoCrawlIssueType::Page404->value] ?? 0)
                + ($counts[SeoCrawlIssueType::BrokenLink->value] ?? 0),
            'on_page_issues' => count($onPagePages),
            'pages_crawled' => count($pages),
            'pages_failed' => count(array_filter($pages, fn (array $page) => ! empty($page['failed']))),
            'category_counts' => array_merge($counts, ['healthy_pages' => $healthy]),
            'inbound' => $inbound,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return array<string, int>
     */
    protected function inboundCounts(array $pages): array
    {
        $inbound = [];
        foreach ($pages as $page) {
            $fromKey = $this->pageKey($page);
            foreach ($page['outbound_urls'] ?? [] as $href) {
                $targetKey = $this->pageKey(['url' => $href]);
                if ($targetKey === $fromKey) {
                    continue;
                }
                $inbound[$targetKey] = ($inbound[$targetKey] ?? 0) + 1;
            }
        }

        return $inbound;
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $details
     * @param  array<string, scalar>  $replace
     * @return array<string, mixed>
     */
    protected function issue(array $page, SeoCrawlIssueType $type, array $details = [], array $replace = []): array
    {
        return [
            'page_key' => $this->pageKey($page),
            'url' => $page['url'] ?? null,
            'path' => $page['path'] ?? '/',
            'type' => $type->value,
            'severity' => $type->severity(),
            'message_ar' => trans('seo_crawl.issues.'.$type->value, $replace, 'ar'),
            'message_en' => trans('seo_crawl.issues.'.$type->value, $replace, 'en'),
            'details' => $details,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $group
     * @param  array<string, mixed>  $page
     */
    protected function otherPath(array $group, array $page): ?string
    {
        $self = $this->pageKey($page);
        foreach ($group as $other) {
            if ($this->pageKey($other) !== $self) {
                return (string) ($other['path'] ?? '');
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $page
     */
    public function pageKey(array $page): string
    {
        $url = (string) ($page['url'] ?? '');

        return sha1(rtrim(preg_replace('#\?.*$#', '', $url) ?? $url, '/'));
    }

    /**
     * @param  array<string, mixed>  $page
     */
    public function isIndexed(array $page): bool
    {
        return ! empty($page['is_html'])
            && (int) ($page['status_code'] ?? 0) === 200
            && ! empty($page['is_indexable']);
    }

    /**
     * @param  array<string, mixed>  $page
     */
    protected function isAuditableHtml(array $page): bool
    {
        return ! empty($page['is_html']) && (int) ($page['status_code'] ?? 0) === 200;
    }

    protected function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
