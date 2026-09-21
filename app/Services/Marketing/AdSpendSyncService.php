<?php

namespace App\Services\Marketing;

use App\Interfaces\AdSpendProviderInterface;
use App\Models\AdSpendDaily;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdSpendSyncService
{
    /**
     * @return array<string, array{synced: int, skipped: bool, reason?: string, missing?: list<string>}>
     */
    public function sync(?Carbon $from = null, ?Carbon $to = null, ?string $platform = null): array
    {
        $to ??= now()->endOfDay();
        $from ??= $to->copy()->subDays(2)->startOfDay();
        $results = [];

        foreach (config('ads.platforms', []) as $key => $meta) {
            if ($platform !== null && $platform !== $key) {
                continue;
            }

            $providerClass = $meta['provider'] ?? null;
            if (! is_string($providerClass) || ! is_subclass_of($providerClass, AdSpendProviderInterface::class)) {
                $results[$key] = ['synced' => 0, 'skipped' => true, 'reason' => 'invalid_provider'];

                continue;
            }

            /** @var AdSpendProviderInterface $provider */
            $provider = app($providerClass);
            if (! $provider->isConfigured()) {
                $results[$key] = [
                    'synced' => 0,
                    'skipped' => true,
                    'reason' => 'not_configured',
                    'missing' => $provider->missingCredentials(),
                ];

                continue;
            }

            try {
                $rows = $provider->fetch($from->copy(), $to->copy());
                $results[$key] = [
                    'synced' => $this->upsert($rows, 'api'),
                    'skipped' => false,
                ];
            } catch (\Throwable $e) {
                Log::warning('Ad spend sync failed', [
                    'platform' => $key,
                    'error' => $e->getMessage(),
                ]);
                $results[$key] = [
                    'synced' => 0,
                    'skipped' => true,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function upsert(array $rows, string $ingestSource = 'manual'): int
    {
        if (! Schema::hasTable('ad_spend_dailies')) {
            return 0;
        }

        $count = 0;
        foreach ($rows as $row) {
            $spentOn = $row['spent_on'] ?? null;
            $platform = $row['platform'] ?? null;
            if (! $spentOn || ! $platform) {
                continue;
            }

            AdSpendDaily::query()->updateOrCreate(
                [
                    'spent_on' => $spentOn,
                    'platform' => $platform,
                    'campaign_id' => (string) ($row['campaign_id'] ?? ''),
                    'keyword' => (string) ($row['keyword'] ?? ''),
                ],
                [
                    'campaign_name' => $row['campaign_name'] ?? null,
                    'spend' => (float) ($row['spend'] ?? 0),
                    'currency' => strtoupper((string) ($row['currency'] ?? 'SAR')),
                    'impressions' => $row['impressions'] ?? null,
                    'clicks' => $row['clicks'] ?? null,
                    'ingest_source' => $ingestSource,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * @return list<array{platform: string, label: string, utm_source: string, configured: bool, missing: list<string>}>
     */
    public function credentialStatus(): array
    {
        $status = [];
        foreach (config('ads.platforms', []) as $key => $meta) {
            $providerClass = $meta['provider'] ?? null;
            $missing = [];
            $configured = false;
            if (is_string($providerClass) && is_subclass_of($providerClass, AdSpendProviderInterface::class)) {
                $provider = app($providerClass);
                $missing = $provider->missingCredentials();
                $configured = $provider->isConfigured();
            }

            $status[] = [
                'platform' => $key,
                'label' => $meta['label'] ?? $key,
                'utm_source' => $meta['utm_source'] ?? $key,
                'configured' => $configured,
                'missing' => $missing,
            ];
        }

        return $status;
    }
}
