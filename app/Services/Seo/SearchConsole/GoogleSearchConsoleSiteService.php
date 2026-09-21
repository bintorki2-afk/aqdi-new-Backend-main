<?php

namespace App\Services\Seo\SearchConsole;

use App\Models\GoogleSeoConnection;
use App\Services\Seo\GoogleAccessTokenService;
use RuntimeException;

class GoogleSearchConsoleSiteService
{
    public function __construct(
        protected GoogleAccessTokenService $tokens,
        protected GoogleSearchConsoleClient $client,
    ) {}

    public function connection(): GoogleSeoConnection
    {
        $connection = $this->tokens->connection();
        if (! $connection->hasSearchConsole()) {
            throw new RuntimeException(trans('api.google_search_console_scope_missing'));
        }

        return $connection;
    }

    public function siteUrl(): string
    {
        $siteUrl = trim((string) $this->connection()->search_console_site_url);
        if ($siteUrl === '') {
            throw new RuntimeException(trans('api.google_search_console_site_missing'));
        }

        return $siteUrl;
    }

    /**
     * @return array{selected_site_url: string|null, items: list<array<string, mixed>>}
     */
    public function list(): array
    {
        $connection = $this->connection();
        $selected = $connection->search_console_site_url;
        $payload = $this->client->listSites();

        $items = [];
        foreach ($payload['siteEntry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $url = (string) ($entry['siteUrl'] ?? '');
            if ($url === '') {
                continue;
            }
            $items[] = [
                'site_url' => $url,
                'permission_level' => $entry['permissionLevel'] ?? null,
                'selected' => $url === $selected,
            ];
        }

        if (! filled($selected) && count($items) === 1) {
            $selected = $this->select($items[0]['site_url']);
            $items[0]['selected'] = true;
        }

        return [
            'selected_site_url' => $selected,
            'items' => $items,
        ];
    }

    public function select(string $siteUrl): string
    {
        $siteUrl = trim($siteUrl);
        if ($siteUrl === '') {
            throw new RuntimeException(trans('api.google_search_console_site_missing'));
        }

        $allowed = [];
        foreach ($this->client->listSites()['siteEntry'] ?? [] as $entry) {
            if (is_array($entry) && filled($entry['siteUrl'] ?? null)) {
                $allowed[] = (string) $entry['siteUrl'];
            }
        }

        if (! in_array($siteUrl, $allowed, true)) {
            throw new RuntimeException(trans('api.google_search_console_site_invalid'));
        }

        $connection = $this->connection();
        $connection->forceFill(['search_console_site_url' => $siteUrl])->save();

        return $siteUrl;
    }
}
