<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoCrawlService;
use Illuminate\Console\Command;
use Throwable;

class SeoCrawlCommand extends Command
{
    protected $signature = 'seo:crawl
        {--url= : Site origin to crawl (default: SEO_CRAWL_BASE_URL / https://aqdi.sa)}
        {--max-pages= : Cap on URLs to fetch}
        {--json : Print the dashboard payload as JSON}';

    protected $description = 'Crawl aqdi.sa (or --url) and store a technical SEO audit';

    public function handle(SeoCrawlService $service): int
    {
        $inProgress = $service->inProgressRun();
        if ($inProgress) {
            $this->error('A crawl is already running (id '.$inProgress->id.'). Wait for it to finish.');

            return self::FAILURE;
        }

        $url = $this->option('url');
        $url = is_string($url) && $url !== '' ? $url : null;
        $maxPages = $this->option('max-pages');
        $maxPages = is_numeric($maxPages) ? (int) $maxPages : null;

        $run = $service->createRun($url, $maxPages);
        $this->info('Starting crawl of '.$run->base_url.' (run #'.$run->id.')');

        try {
            $run = $service->execute(
                $run->id,
                $maxPages,
                function (string $url, int $n, int $max) {
                    $this->line(sprintf('[%d/%d] %s', $n, $max, $url));
                }
            );
        } catch (Throwable $e) {
            $this->error('Crawl failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($service->dashboard($run), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $dashboard = $service->dashboard($run);
        $this->newLine();
        $this->info('Finished: '.$run->pages_crawled.' URLs in '.$run->started_at?->diffForHumans($run->finished_at, true));
        $this->table(
            ['metric', 'count'],
            [
                ['indexed_pages', $dashboard['summary']['indexed_pages']['count']],
                ['healthy_pages', $dashboard['summary']['healthy_pages']['count']],
                ['broken_pages', $dashboard['summary']['broken_pages']['count']],
                ['on_page_issues', $dashboard['summary']['on_page_issues']['count']],
            ]
        );

        $this->table(
            ['category', 'severity', 'count'],
            collect($dashboard['categories'])->map(fn (array $row) => [
                $row['type'],
                $row['severity'],
                $row['count'],
            ])->all()
        );

        $issues = $run->issues()->limit(15)->get(['path', 'type', 'severity', 'message_en']);
        if ($issues->isNotEmpty()) {
            $this->table(
                ['page', 'problem', 'severity'],
                $issues->map(fn ($issue) => [
                    $issue->path,
                    $issue->message_en,
                    $issue->severity,
                ])->all()
            );
        }

        return self::SUCCESS;
    }
}
