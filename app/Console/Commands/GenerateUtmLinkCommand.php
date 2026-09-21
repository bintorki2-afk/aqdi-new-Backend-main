<?php

namespace App\Console\Commands;

use App\Support\Marketing\UtmAttribution;
use Illuminate\Console\Command;

class GenerateUtmLinkCommand extends Command
{
    protected $signature = 'ads:utm-link
        {url : Landing URL without UTM}
        {--source=google : utm_source (google|meta|tiktok|twitter|snapchat|whatsapp|paid)}
        {--medium=cpc : utm_medium}
        {--campaign= : utm_campaign (required)}
        {--term= : utm_term / keyword}
        {--content= : utm_content / ad set}';

    protected $description = 'Build a landing URL with the standard Aqdi UTM template for ad accounts';

    public function handle(): int
    {
        $campaign = trim((string) $this->option('campaign'));
        if ($campaign === '') {
            $this->error('Pass --campaign= so spend can be matched to orders.');

            return self::FAILURE;
        }

        $url = (string) $this->argument('url');
        $query = UtmAttribution::buildQuery(
            (string) $this->option('source'),
            $campaign,
            $this->option('term') ? (string) $this->option('term') : null,
            $this->option('content') ? (string) $this->option('content') : null,
            (string) $this->option('medium')
        );

        $separator = str_contains($url, '?') ? '&' : '?';
        $this->line($url.$separator.$query);

        return self::SUCCESS;
    }
}
