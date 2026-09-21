<?php

namespace App\Interfaces;

use Illuminate\Support\Carbon;

interface AdSpendProviderInterface
{
    public function platform(): string;

    public function isConfigured(): bool;

    /**
     * @return list<string>
     */
    public function missingCredentials(): array;

    /**
     * @return list<array{
     *     spent_on: string,
     *     platform: string,
     *     campaign_id: string,
     *     campaign_name: string|null,
     *     keyword: string,
     *     spend: float,
     *     currency: string,
     *     impressions: int|null,
     *     clicks: int|null
     * }>
     */
    public function fetch(Carbon $from, Carbon $to): array;
}
