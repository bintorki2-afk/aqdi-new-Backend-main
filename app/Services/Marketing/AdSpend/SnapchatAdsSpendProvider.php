<?php

namespace App\Services\Marketing\AdSpend;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SnapchatAdsSpendProvider extends AbstractAdSpendProvider
{
    public function platform(): string
    {
        return 'snapchat';
    }

    public function fetch(Carbon $from, Carbon $to): array
    {
        $token = $this->accessToken();
        $accountId = $this->credential('ad_account_id');

        $response = Http::withToken($token)->timeout(40)->get(
            'https://adsapi.snapchat.com/v1/adaccounts/'.$accountId.'/stats',
            [
                'granularity' => 'DAY',
                'start_time' => $from->copy()->startOfDay()->toIso8601String(),
                'end_time' => $to->copy()->endOfDay()->toIso8601String(),
                'breakdown' => 'campaign',
                'fields' => 'spend,impressions,swipes',
            ]
        );

        if (! $response->successful()) {
            Log::warning('Snapchat Ads stats failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Snapchat Ads API failed HTTP '.$response->status());
        }

        $rows = [];
        foreach ((array) $response->json('timeseries_stats', $response->json('total_stats', [])) as $stat) {
            $campaignId = (string) data_get($stat, 'id', data_get($stat, 'campaign_id', ''));
            $campaignName = data_get($stat, 'campaign_name');
            $series = data_get($stat, 'timeseries', data_get($stat, 'timeseries_stat.timeseries', []));
            if (! is_array($series) || $series === []) {
                $rows[] = $this->mapRow(
                    $from,
                    $campaignId,
                    is_string($campaignName) ? $campaignName : null,
                    (float) data_get($stat, 'spend', 0) / 1_000_000,
                    '',
                    (int) data_get($stat, 'impressions', 0),
                    (int) data_get($stat, 'swipes', 0),
                );

                continue;
            }

            foreach ($series as $point) {
                $start = (string) data_get($point, 'start_time', $from->toDateString());
                $stats = data_get($point, 'stats', $point);
                $spendMicros = (float) data_get($stats, 'spend', 0);
                $rows[] = $this->mapRow(
                    substr($start, 0, 10),
                    $campaignId,
                    is_string($campaignName) ? $campaignName : null,
                    $spendMicros > 1000 ? $spendMicros / 1_000_000 : $spendMicros,
                    '',
                    isset($stats['impressions']) ? (int) $stats['impressions'] : null,
                    isset($stats['swipes']) ? (int) $stats['swipes'] : null,
                );
            }
        }

        return $rows;
    }

    private function accessToken(): string
    {
        $response = Http::asForm()->timeout(20)->post('https://accounts.snapchat.com/login/oauth2/access_token', [
            'client_id' => $this->credential('client_id'),
            'client_secret' => $this->credential('client_secret'),
            'refresh_token' => $this->credential('refresh_token'),
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful() || ! $response->json('access_token')) {
            throw new \RuntimeException('Snapchat OAuth failed: '.$response->body());
        }

        return (string) $response->json('access_token');
    }
}
