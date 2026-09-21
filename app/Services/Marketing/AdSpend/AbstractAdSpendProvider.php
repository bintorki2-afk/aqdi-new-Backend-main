<?php

namespace App\Services\Marketing\AdSpend;

use App\Interfaces\AdSpendProviderInterface;
use Illuminate\Support\Carbon;

abstract class AbstractAdSpendProvider implements AdSpendProviderInterface
{
    abstract public function platform(): string;

    public function isConfigured(): bool
    {
        return $this->missingCredentials() === [];
    }

    /**
     * @return list<string>
     */
    public function missingCredentials(): array
    {
        $config = config('ads.platforms.'.$this->platform(), []);
        $credentials = $config['credentials'] ?? [];
        $required = $config['required'] ?? [];
        $missing = [];

        foreach ($required as $key) {
            $value = $credentials[$key] ?? null;
            if (! is_string($value) || trim($value) === '') {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * @return array<string, mixed>
     */
    protected function credentials(): array
    {
        return config('ads.platforms.'.$this->platform().'.credentials', []);
    }

    protected function credential(string $key): string
    {
        return trim((string) ($this->credentials()[$key] ?? ''));
    }

    protected function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     spent_on: string,
     *     platform: string,
     *     campaign_id: string,
     *     campaign_name: string|null,
     *     keyword: string,
     *     spend: float,
     *     currency: string,
     *     impressions: int|null,
     *     clicks: int|null
     * }
     */
    protected function mapRow(
        Carbon|string $spentOn,
        string $campaignId,
        ?string $campaignName,
        float $spend,
        ?string $keyword = null,
        ?int $impressions = null,
        ?int $clicks = null,
        string $currency = 'SAR',
    ): array {
        $date = $spentOn instanceof Carbon ? $spentOn->toDateString() : $spentOn;

        return [
            'spent_on' => $date,
            'platform' => $this->platform(),
            'campaign_id' => $campaignId,
            'campaign_name' => $campaignName,
            'keyword' => $keyword ?? '',
            'spend' => round($spend, 2),
            'currency' => strtoupper($currency),
            'impressions' => $impressions,
            'clicks' => $clicks,
        ];
    }
}
