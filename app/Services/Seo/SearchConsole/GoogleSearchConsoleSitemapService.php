<?php

namespace App\Services\Seo\SearchConsole;

class GoogleSearchConsoleSitemapService
{
    public function __construct(
        protected GoogleSearchConsoleClient $client,
        protected GoogleSearchConsoleSiteService $sites,
    ) {}

    /**
     * @return array{site_url: string, items: list<array<string, mixed>>}
     */
    public function list(): array
    {
        $siteUrl = $this->sites->siteUrl();
        $payload = $this->client->listSitemaps($siteUrl);

        $items = [];
        foreach ($payload['sitemap'] ?? [] as $sitemap) {
            if (! is_array($sitemap)) {
                continue;
            }
            $items[] = [
                'path' => (string) ($sitemap['path'] ?? ''),
                'type' => $sitemap['type'] ?? null,
                'last_submitted' => $sitemap['lastSubmitted'] ?? null,
                'last_downloaded' => $sitemap['lastDownloaded'] ?? null,
                'is_pending' => (bool) ($sitemap['isPending'] ?? false),
                'is_sitemaps_index' => (bool) ($sitemap['isSitemapsIndex'] ?? false),
                'warnings' => (int) ($sitemap['warnings'] ?? 0),
                'errors' => (int) ($sitemap['errors'] ?? 0),
            ];
        }

        return [
            'site_url' => $siteUrl,
            'items' => $items,
        ];
    }
}
