<?php

namespace App\Services\Seo;

use App\Models\SeoCrawlRun;
use App\Services\FirebaseRealtimeDatabaseService;

class SeoCrawlFirebaseStatus
{
    protected float $lastProgressAt = 0.0;

    public function __construct(
        protected FirebaseRealtimeDatabaseService $realtime
    ) {}

    public function publish(SeoCrawlRun $run, array $progress = []): void
    {
        $this->realtime->put(
            (string) config('seo_crawl.firebase_path', 'seo_crawl/status'),
            $this->payload($run, $progress)
        );
    }

    public function progress(SeoCrawlRun $run, string $url, int $current, int $max): void
    {
        $interval = (float) config('seo_crawl.firebase_progress_interval', 1);
        $now = microtime(true);
        $isLast = $max > 0 && $current >= $max;

        if (! $isLast && $this->lastProgressAt > 0 && ($now - $this->lastProgressAt) < $interval) {
            return;
        }

        $this->lastProgressAt = $now;
        $this->publish($run, [
            'current_url' => $url,
            'progress_current' => $current,
            'progress_max' => $max,
        ]);
    }

    /**
     * @param  array{current_url?: string|null, progress_current?: int, progress_max?: int}  $progress
     * @return array<string, mixed>
     */
    public function payload(SeoCrawlRun $run, array $progress = []): array
    {
        $current = (int) ($progress['progress_current'] ?? $run->pages_crawled ?? 0);
        $max = (int) ($progress['progress_max'] ?? 0);
        if ($max <= 0) {
            $max = max($current, (int) config('seo_crawl.max_pages', 400));
        }

        $percent = $max > 0 ? (int) min(100, round(($current / $max) * 100)) : 0;
        if (in_array($run->status, [SeoCrawlRun::STATUS_COMPLETED, SeoCrawlRun::STATUS_STOPPED], true)) {
            $percent = 100;
        }

        return [
            'id' => (int) $run->id,
            'status' => (string) $run->status,
            'base_url' => (string) $run->base_url,
            'current_url' => (string) ($progress['current_url'] ?? ''),
            'pages_crawled' => $current,
            'pages_failed' => (int) ($run->pages_failed ?? 0),
            'progress_current' => $current,
            'progress_max' => $max,
            'percent' => $percent,
            'indexed_pages' => (int) ($run->indexed_pages ?? 0),
            'healthy_pages' => (int) ($run->healthy_pages ?? 0),
            'broken_pages' => (int) ($run->broken_pages ?? 0),
            'on_page_issues' => (int) ($run->on_page_issues ?? 0),
            'error_message' => (string) ($run->error_message ?? ''),
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
