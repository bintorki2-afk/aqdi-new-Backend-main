<?php

namespace App\Services;

use Google_Client as GoogleClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes JSON nodes to Firebase Realtime Database via the REST API.
 */
class FirebaseRealtimeDatabaseService
{
    public function isEnabled(): bool
    {
        if (! config('seo_crawl.firebase_status', true)) {
            return false;
        }

        if ($this->databaseUrl() === '') {
            return false;
        }

        if (app()->environment('testing') && ! config('seo_crawl.firebase_status_in_tests')) {
            return false;
        }

        return true;
    }

    /**
     * Replace a node at the given path (no leading slash).
     *
     * @param  array<string, mixed>  $data
     */
    public function put(string $path, array $data): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $url = $this->nodeUrl($path);
        if ($url === null) {
            return;
        }

        try {
            $token = $this->accessToken();
            if ($token === null) {
                return;
            }

            $response = Http::timeout(8)
                ->acceptJson()
                ->withToken($token)
                ->put($url, $data);

            if ($response->failed()) {
                Log::warning('Firebase Realtime Database write failed', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Firebase Realtime Database write error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function databaseUrl(): string
    {
        return rtrim((string) config('services.firebase.database_url'), '/');
    }

    protected function nodeUrl(string $path): ?string
    {
        $base = $this->databaseUrl();
        if ($base === '') {
            return null;
        }

        $path = trim(str_replace('.', '', $path), '/');
        if ($path === '') {
            return null;
        }

        return $base.'/'.$path.'.json';
    }

    protected function accessToken(): ?string
    {
        $override = config('services.firebase.database_access_token');
        if (is_string($override) && $override !== '') {
            return $override;
        }

        try {
            return Cache::remember('firebase:rtdb:access_token', 50 * 60, function (): ?string {
                $credentials = (string) config(
                    'services.firebase.credentials',
                    storage_path('app/aqdi-test-34027147e050.json')
                );
                $path = $this->resolveCredentialsPath($credentials);
                if (! is_file($path)) {
                    Log::warning('Firebase credentials file missing for Realtime Database', ['path' => $path]);

                    return null;
                }

                $client = new GoogleClient();
                $client->setAuthConfig($path);
                $client->addScope('https://www.googleapis.com/auth/firebase.database');
                $client->addScope('https://www.googleapis.com/auth/userinfo.email');
                $client->refreshTokenWithAssertion();
                $token = $client->getAccessToken();

                return is_array($token) ? ($token['access_token'] ?? null) : null;
            });
        } catch (Throwable $e) {
            Log::warning('Firebase Realtime Database token failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function resolveCredentialsPath(string $path): string
    {
        if ($path !== '' && (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path))) {
            return $path;
        }

        return base_path($path);
    }
}
