<?php

namespace App\Services\Seo;

use App\Models\Employee;
use App\Models\GoogleSeoConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use RuntimeException;

class GoogleSeoOAuthService
{
    public const SCOPES = [
        'openid',
        'email',
        'profile',
        'https://www.googleapis.com/auth/webmasters.readonly',
        'https://www.googleapis.com/auth/analytics.readonly',
    ];

    public function current(): ?GoogleSeoConnection
    {
        return GoogleSeoConnection::query()
            ->where('provider', GoogleSeoConnection::PROVIDER)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $connection = $this->current();
        $connected = $connection?->isConnected() ?? false;

        return [
            'connected' => $connected,
            'google_email' => $connected ? $connection?->google_email : null,
            'search_console' => $connection?->hasSearchConsole() ?? false,
            'analytics' => $connected && ($connection?->hasScope('analytics.readonly') ?? false),
            'search_console_site_url' => $connected ? $connection?->search_console_site_url : null,
            'analytics_property_id' => $connected ? $connection?->analytics_property_id : null,
            'connected_at' => $connected ? $connection?->updated_at?->toIso8601String() : null,
        ];
    }

    public function authorizationUrl(Employee $employee): string
    {
        $this->assertConfigured();

        $state = Str::random(40);
        Cache::put($this->stateKey($state), $employee->id, now()->addMinutes(15));

        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl($this->redirectUri())
            ->scopes(self::SCOPES)
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
                'state' => $state,
            ])
            ->redirect()
            ->getTargetUrl();
    }

    public function complete(string $state, SocialiteUser $googleUser): GoogleSeoConnection
    {
        $employeeId = Cache::pull($this->stateKey($state));
        if (! $employeeId) {
            throw new RuntimeException(trans('api.google_seo_invalid_state'));
        }

        $refresh = $googleUser->refreshToken;
        $existing = $this->current();
        if (! $refresh && $existing?->refresh_token) {
            $refresh = $existing->refresh_token;
        }

        if (! $refresh && ! $googleUser->token) {
            throw new RuntimeException(trans('api.google_seo_no_token'));
        }

        $scopes = $googleUser->approvedScopes ?: self::SCOPES;
        $expiresIn = (int) ($googleUser->expiresIn ?? 3600);

        return GoogleSeoConnection::query()->updateOrCreate(
            ['provider' => GoogleSeoConnection::PROVIDER],
            [
                'google_email' => $googleUser->getEmail(),
                'google_user_id' => $googleUser->getId(),
                'access_token' => $googleUser->token,
                'refresh_token' => $refresh,
                'token_expires_at' => now()->addSeconds(max($expiresIn, 60)),
                'scopes' => array_values($scopes),
                'search_console_site_url' => $existing?->search_console_site_url
                    ?: rtrim((string) config('seo_crawl.base_url', 'https://aqdi.sa'), '/').'/',
                'analytics_property_id' => $existing?->analytics_property_id,
                'connected_by_employee_id' => (int) $employeeId,
            ]
        );
    }

    public function disconnect(): void
    {
        GoogleSeoConnection::query()
            ->where('provider', GoogleSeoConnection::PROVIDER)
            ->delete();
    }

    public function redirectUri(): string
    {
        $configured = trim((string) config('services.google.seo_redirect'));

        return $configured !== ''
            ? $configured
            : url('/api/admin/seo-google/callback');
    }

    public function frontendRedirect(bool $ok, ?string $error = null): string
    {
        $base = rtrim((string) (
            config('services.google.seo_frontend_redirect')
            ?: config('services.moyasar.payment_frontend_url')
            ?: 'http://localhost:3000'
        ), '/');

        $query = $ok
            ? ['google_seo' => 'connected']
            : ['google_seo' => 'error', 'message' => $error ?: 'oauth_failed'];

        return $base.'/seo?'.http_build_query($query);
    }

    public function assertConfigured(): void
    {
        if (trim((string) config('services.google.client_id')) === ''
            || trim((string) config('services.google.client_secret')) === '') {
            throw new RuntimeException(trans('api.google_seo_not_configured'));
        }
    }

    protected function stateKey(string $state): string
    {
        return 'google-seo-oauth:'.$state;
    }
}
