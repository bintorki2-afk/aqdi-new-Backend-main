<?php

namespace App\Services\Marketing\AdSpend;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokAdsSpendProvider extends AbstractAdSpendProvider
{
    public function platform(): string
    {
        return 'tiktok';
    }

    public function fetch(Carbon $from, Carbon $to): array
    {
        $response = Http::withHeaders([
            'Access-Token' => $this->credential('access_token'),
            'Content-Type' => 'application/json',
        ])->timeout(40)->post('https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/', [
            'advertiser_id' => $this->credential('advertiser_id'),
            'report_type' => 'BASIC',
            'data_level' => 'AUCTION_CAMPAIGN',
            'dimensions' => ['stat_time_day', 'campaign_id'],
            'metrics' => ['spend', 'impressions', 'clicks', 'campaign_name'],
            'start_date' => $from->toDateString(),
            'end_date' => $to->toDateString(),
            'page_size' => 1000,
        ]);

        if (! $response->successful() || (int) $response->json('code', 1) !== 0) {
            Log::warning('TikTok Ads report failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('TikTok Ads API failed HTTP '.$response->status());
        }

        $rows = [];
        foreach ((array) $response->json('data.list', []) as $row) {
            $dimensions = $row['dimensions'] ?? $row;
            $metrics = $row['metrics'] ?? $row;
            $rows[] = $this->mapRow(
                substr((string) ($dimensions['stat_time_day'] ?? $from->toDateString()), 0, 10),
                (string) ($dimensions['campaign_id'] ?? ''),
                $metrics['campaign_name'] ?? ($dimensions['campaign_name'] ?? null),
                (float) ($metrics['spend'] ?? 0),
                '',
                isset($metrics['impressions']) ? (int) $metrics['impressions'] : null,
                isset($metrics['clicks']) ? (int) $metrics['clicks'] : null,
            );
        }

        return $rows;
    }
}
