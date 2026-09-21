<?php

namespace App\Services\Seo\SearchConsole;

use App\Services\Seo\GoogleAccessTokenService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleSearchConsoleClient
{
    public const BASE_URL = 'https://www.googleapis.com/webmasters/v3';

    public function __construct(protected GoogleAccessTokenService $tokens) {}

    /**
     * @return array<string, mixed>
     */
    public function listSites(): array
    {
        return $this->get('/sites');
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function querySearchAnalytics(string $siteUrl, array $body): array
    {
        return $this->post('/sites/'.$this->encodeSite($siteUrl).'/searchAnalytics/query', $body);
    }

    /**
     * @return array<string, mixed>
     */
    public function listSitemaps(string $siteUrl): array
    {
        return $this->get('/sites/'.$this->encodeSite($siteUrl).'/sitemaps');
    }

    /**
     * @return array<string, mixed>
     */
    protected function get(string $path): array
    {
        return $this->send('get', $path);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function post(string $path, array $body): array
    {
        return $this->send('post', $path, $body);
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    protected function send(string $method, string $path, ?array $body = null): array
    {
        $request = Http::withToken($this->tokens->accessToken())
            ->acceptJson()
            ->timeout(45);

        $url = self::BASE_URL.$path;
        $response = $method === 'post'
            ? $request->post($url, $body ?? [])
            : $request->get($url);

        if (! $response->successful()) {
            Log::warning('Google Search Console API failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(trans('api.google_search_console_failed'));
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    protected function encodeSite(string $siteUrl): string
    {
        return rawurlencode($siteUrl);
    }
}
