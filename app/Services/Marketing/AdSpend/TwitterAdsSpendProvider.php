<?php

namespace App\Services\Marketing\AdSpend;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwitterAdsSpendProvider extends AbstractAdSpendProvider
{
    public function platform(): string
    {
        return 'twitter';
    }

    public function fetch(Carbon $from, Carbon $to): array
    {
        $accountId = $this->credential('account_id');
        $response = Http::withToken($this->credential('bearer_token'))
            ->timeout(40)
            ->get('https://ads-api.twitter.com/12/stats/accounts/'.$accountId, [
                'entity' => 'CAMPAIGN',
                'granularity' => 'DAY',
                'metric_groups' => 'BILLING',
                'start_time' => $from->copy()->startOfDay()->toIso8601String(),
                'end_time' => $to->copy()->endOfDay()->toIso8601String(),
                'placement' => 'ALL_ON_TWITTER',
            ]);

        if (! $response->successful()) {
            Log::warning('X Ads stats failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException(
                'X Ads API failed HTTP '.$response->status().'. Until API access is approved, import spend via POST /api/admin/reports/marketing/spend.'
            );
        }

        $rows = [];
        foreach ((array) $response->json('data', []) as $row) {
            $campaignId = (string) ($row['id'] ?? '');
            $dates = $row['id_data'][0]['date_range'] ?? null;
            $billed = $row['id_data'][0]['metrics']['billed_charge_local_micro'] ?? [];
            if (! is_array($billed)) {
                continue;
            }

            foreach ($billed as $index => $micros) {
                $spentOn = $from->copy()->addDays((int) $index)->toDateString();
                if (is_array($dates) && isset($dates[$index])) {
                    $spentOn = substr((string) $dates[$index], 0, 10);
                }
                $rows[] = $this->mapRow(
                    $spentOn,
                    $campaignId,
                    $row['name'] ?? null,
                    ((float) $micros) / 1_000_000,
                );
            }
        }

        return $rows;
    }
}
