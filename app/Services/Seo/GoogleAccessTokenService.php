<?php

namespace App\Services\Seo;

use App\Models\GoogleSeoConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleAccessTokenService
{
    public function __construct(protected GoogleSeoOAuthService $oauth) {}

    public function connection(): GoogleSeoConnection
    {
        $connection = $this->oauth->current();
        if (! $connection?->isConnected()) {
            throw new RuntimeException(trans('api.google_seo_not_connected'));
        }

        return $connection;
    }

    public function accessToken(): string
    {
        $connection = $this->connection();

        if ($this->isFresh($connection) && filled($connection->access_token)) {
            return (string) $connection->access_token;
        }

        return $this->refresh($connection);
    }

    protected function isFresh(GoogleSeoConnection $connection): bool
    {
        return $connection->token_expires_at
            && $connection->token_expires_at->gt(now()->addSeconds(60));
    }

    protected function refresh(GoogleSeoConnection $connection): string
    {
        if (! filled($connection->refresh_token)) {
            throw new RuntimeException(trans('api.google_seo_no_token'));
        }

        $this->oauth->assertConfigured();

        $response = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        $accessToken = $response->json('access_token');
        if (! $response->successful() || ! filled($accessToken)) {
            throw new RuntimeException(trans('api.google_seo_no_token'));
        }

        $expiresIn = (int) ($response->json('expires_in') ?? 3600);
        $connection->forceFill([
            'access_token' => $accessToken,
            'token_expires_at' => now()->addSeconds(max($expiresIn, 60)),
        ])->save();

        return (string) $accessToken;
    }
}
