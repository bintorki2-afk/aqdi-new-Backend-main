<?php

namespace App\Jobs;

use App\Services\Seo\SeoCrawlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunSeoCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public int $tries = 1;

    public function __construct(public int $runId, public ?int $maxPages = null) {}

    public function handle(SeoCrawlService $service): void
    {
        $service->execute($this->runId, $this->maxPages);
    }
}
