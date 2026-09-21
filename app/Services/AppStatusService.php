<?php

namespace App\Services;

use App\Models\AppVersion;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Schema;

class AppStatusService
{
    public const WEBSITE_KEY = GeneralSetting::WEBSITE_STATUS;

    public const MOBILE_KEY = GeneralSetting::MOBILE_STATUS;

    public function ensureCatalog(): void
    {
        if (Schema::hasTable('general_settings')) {
            GeneralSetting::syncFromConfig();
        }

        if (! Schema::hasTable('app_versions')) {
            return;
        }

        foreach (AppVersion::PLATFORMS as $platform) {
            AppVersion::query()->firstOrCreate(
                ['platform' => $platform],
                [
                    'force_update' => false,
                    'message_ar' => 'يرجى تحديث التطبيق لمتابعة الاستخدام',
                    'message_en' => 'Please update the app to continue',
                ]
            );
        }
    }

    public function isWebsiteOpen(): bool
    {
        if (! Schema::hasTable('general_settings')) {
            return true;
        }

        return GeneralSetting::isEnabled(self::WEBSITE_KEY, true);
    }

    public function isMobileOpen(): bool
    {
        if (! Schema::hasTable('general_settings')) {
            return true;
        }

        return GeneralSetting::isEnabled(self::MOBILE_KEY, true);
    }

    /**
     * Public website-only status (web SPA / Blade AJAX).
     *
     * @return array{is_open: bool, message: string|null, message_ar: string|null, message_en: string|null}
     */
    public function websitePayload(): array
    {
        $open = $this->isWebsiteOpen();
        $closedMessage = trans('api.website_closed');

        return [
            'is_open' => $open,
            'message' => $open ? null : $closedMessage,
            'message_ar' => $open ? null : trans('api.website_closed', [], 'ar'),
            'message_en' => $open ? null : trans('api.website_closed', [], 'en'),
        ];
    }

    /**
     * True when the caller is the public website (not the mobile app, not admin).
     */
    public function isWebsiteClient(\Illuminate\Http\Request $request): bool
    {
        $raw = $request->header('X-Client')
            ?? $request->header('X-Platform')
            ?? $request->query('platform')
            ?? $request->input('platform');

        if (! is_string($raw) || trim($raw) === '') {
            return false;
        }

        return $this->normalizePlatform($raw) === 'website';
    }

    /**
     * Public payload for website + mobile + version check.
     *
     * @return array<string, mixed>
     */
    public function publicPayload(?string $platform = null, ?string $currentVersion = null): array
    {
        $this->ensureCatalog();

        $platform = $this->normalizePlatform($platform);

        $payload = [
            'website' => $this->websitePayload(),
            'mobile' => [
                'is_open' => $this->isMobileOpen(),
            ],
            'ios' => $this->platformPayload(AppVersion::PLATFORM_IOS, $platform === AppVersion::PLATFORM_IOS ? $currentVersion : null),
            'android' => $this->platformPayload(AppVersion::PLATFORM_ANDROID, $platform === AppVersion::PLATFORM_ANDROID ? $currentVersion : null),
        ];

        if ($platform === 'website') {
            $payload['update'] = $payload['website'];
            $payload['platform'] = 'website';

            return $payload;
        }

        if ($platform !== null) {
            $payload['update'] = $payload[$platform];
            $payload['platform'] = $platform;
            $payload['current_version'] = $this->normalizeVersion($currentVersion);
        }

        return $payload;
    }

    /**
     * Admin payload (same shape as public, without client-specific update block).
     *
     * @return array<string, mixed>
     */
    public function adminPayload(): array
    {
        $this->ensureCatalog();

        return [
            'website' => [
                'is_open' => $this->isWebsiteOpen(),
                'key' => self::WEBSITE_KEY,
            ],
            'mobile' => [
                'is_open' => $this->isMobileOpen(),
                'key' => self::MOBILE_KEY,
            ],
            'ios' => $this->platformPayload(AppVersion::PLATFORM_IOS),
            'android' => $this->platformPayload(AppVersion::PLATFORM_ANDROID),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(array $data): array
    {
        $this->ensureCatalog();

        if (array_key_exists('website', $data)) {
            GeneralSetting::setEnabled(self::WEBSITE_KEY, $this->extractIsOpen($data['website']));
        }

        if (array_key_exists('mobile', $data)) {
            GeneralSetting::setEnabled(self::MOBILE_KEY, $this->extractIsOpen($data['mobile']));
        }

        foreach (AppVersion::PLATFORMS as $platform) {
            if (! array_key_exists($platform, $data) || ! is_array($data[$platform])) {
                continue;
            }
            $this->updatePlatform($platform, $data[$platform]);
        }

        return $this->adminPayload();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updatePlatform(string $platform, array $data): void
    {
        $row = AppVersion::query()->firstOrCreate(['platform' => $platform]);
        $updates = [];

        foreach (['latest_version', 'min_version', 'store_url', 'message_ar', 'message_en'] as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                $updates[$field] = $value === '' ? null : $value;
            }
        }

        if (array_key_exists('force_update', $data)) {
            $updates['force_update'] = (bool) $data['force_update'];
        }

        if ($updates !== []) {
            $row->update($updates);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function platformPayload(string $platform, ?string $currentVersion = null): array
    {
        $row = Schema::hasTable('app_versions')
            ? AppVersion::query()->where('platform', $platform)->first()
            : null;

        $latest = $this->normalizeVersion($row?->latest_version);
        $min = $this->normalizeVersion($row?->min_version);
        $current = $this->normalizeVersion($currentVersion);
        $adminForce = (bool) ($row?->force_update ?? false);

        $belowMin = $current !== null && $min !== null && version_compare($current, $min, '<');
        $belowLatest = $current !== null && $latest !== null && version_compare($current, $latest, '<');
        $forceUpdate = $adminForce || $belowMin;
        $optionalUpdate = ! $forceUpdate && $belowLatest;

        return [
            'platform' => $platform,
            'latest_version' => $latest,
            'min_version' => $min,
            'force_update' => $forceUpdate,
            'optional_update' => $optionalUpdate,
            'store_url' => $row?->store_url,
            'message_ar' => $row?->message_ar,
            'message_en' => $row?->message_en,
        ];
    }

    private function extractIsOpen(mixed $value): bool
    {
        if (is_array($value)) {
            return (bool) ($value['is_open'] ?? false);
        }

        return (bool) $value;
    }

    private function normalizePlatform(?string $platform): ?string
    {
        if ($platform === null || trim($platform) === '') {
            return null;
        }

        $normalized = strtolower(trim($platform));

        return match ($normalized) {
            'website', 'web' => 'website',
            'ios', 'iphone', 'apple', 'apple_store', 'app_store', 'appstore' => AppVersion::PLATFORM_IOS,
            'android', 'google', 'google_play', 'play' => AppVersion::PLATFORM_ANDROID,
            default => null,
        };
    }

    private function normalizeVersion(?string $version): ?string
    {
        if ($version === null) {
            return null;
        }

        $version = trim($version);
        if ($version === '') {
            return null;
        }

        return ltrim($version, 'vV');
    }
}
