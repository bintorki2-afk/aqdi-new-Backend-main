<?php

namespace App\Services\Marketing\AdSpend;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdsSpendProvider extends AbstractAdSpendProvider
{
    public function platform(): string
    {
        return 'meta';
    }

    public function fetch(Carbon $from, Carbon $to): array
    {
        $accountId = $this->credential('ad_account_id');
        if (! str_starts_with($accountId, 'act_')) {
            $accountId = 'act_'.$this->digits($accountId);
        }

        $rows = [];
        $url = 'https://graph.facebook.com/v21.0/'.$accountId.'/insights';
        $query = [
            'fields' => 'campaign_id,campaign_name,spend,impressions,clicks',
            'level' => 'campaign',
            'time_increment' => 1,
            'time_range' => json_encode([
                'since' => $from->toDateString(),
                'until' => $to->toDateString(),
            ]),
            'access_token' => $this->credential('access_token'),
            'limit' => 500,
        ];

        do {
            $response = Http::timeout(30)->get($url, $query);
            if (! $response->successful()) {
                Log::warning('Meta Ads insights failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \RuntimeException('Meta Ads API failed HTTP '.$response->status());
            }

            foreach ((array) $response->json('data', []) as $row) {
                $rows[] = $this->mapRow(
                    (string) ($row['date_start'] ?? $from->toDateString()),
                    (string) ($row['campaign_id'] ?? ''),
                    $row['campaign_name'] ?? null,
                    (float) ($row['spend'] ?? 0),
                    '',
                    isset($row['impressions']) ? (int) $row['impressions'] : null,
                    isset($row['clicks']) ? (int) $row['clicks'] : null,
                );
            }

            $next = $response->json('paging.next');
            $url = is_string($next) ? $next : '';
            $query = [];
        } while ($url !== '');

        return $rows;
    }
}
