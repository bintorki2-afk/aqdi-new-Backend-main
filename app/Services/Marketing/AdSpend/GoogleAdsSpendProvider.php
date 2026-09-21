<?php

namespace App\Services\Marketing\AdSpend;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAdsSpendProvider extends AbstractAdSpendProvider
{
    public function platform(): string
    {
        return 'google';
    }

    public function fetch(Carbon $from, Carbon $to): array
    {
        $token = $this->accessToken();
        $customerId = $this->digits($this->credential('customer_id'));
        $loginCustomerId = $this->digits($this->credential('login_customer_id')) ?: $customerId;

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'developer-token' => $this->credential('developer_token'),
            'login-customer-id' => $loginCustomerId,
        ];

        $campaignQuery = sprintf(
            'SELECT campaign.id, campaign.name, segments.date, metrics.cost_micros, metrics.impressions, metrics.clicks FROM campaign WHERE segments.date BETWEEN \'%s\' AND \'%s\'',
            $from->toDateString(),
            $to->toDateString()
        );

        $rows = [];
        foreach ($this->search($customerId, $headers, $campaignQuery) as $result) {
            $costMicros = (float) data_get($result, 'metrics.costMicros', data_get($result, 'metrics.cost_micros', 0));
            $rows[] = $this->mapRow(
                (string) data_get($result, 'segments.date'),
                (string) data_get($result, 'campaign.id'),
                data_get($result, 'campaign.name'),
                $costMicros / 1_000_000,
                '',
                (int) data_get($result, 'metrics.impressions', 0),
                (int) data_get($result, 'metrics.clicks', 0),
            );
        }

        $termQuery = sprintf(
            'SELECT search_term_view.search_term, campaign.id, campaign.name, segments.date, metrics.cost_micros FROM search_term_view WHERE segments.date BETWEEN \'%s\' AND \'%s\'',
            $from->toDateString(),
            $to->toDateString()
        );

        foreach ($this->search($customerId, $headers, $termQuery) as $result) {
            $costMicros = (float) data_get($result, 'metrics.costMicros', data_get($result, 'metrics.cost_micros', 0));
            $rows[] = $this->mapRow(
                (string) data_get($result, 'segments.date'),
                (string) data_get($result, 'campaign.id'),
                data_get($result, 'campaign.name'),
                $costMicros / 1_000_000,
                (string) data_get($result, 'searchTermView.searchTerm', data_get($result, 'search_term_view.search_term', '')),
            );
        }

        return $rows;
    }

    private function accessToken(): string
    {
        $response = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->credential('client_id'),
            'client_secret' => $this->credential('client_secret'),
            'refresh_token' => $this->credential('refresh_token'),
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful() || ! $response->json('access_token')) {
            throw new \RuntimeException('Google Ads OAuth failed: '.$response->body());
        }

        return (string) $response->json('access_token');
    }

    /**
     * @param  array<string, string>  $headers
     * @return list<array<string, mixed>>
     */
    private function search(string $customerId, array $headers, string $query): array
    {
        $response = Http::withHeaders($headers)
            ->timeout(45)
            ->post('https://googleads.googleapis.com/v18/customers/'.$customerId.'/googleAds:search', [
                'query' => $query,
            ]);

        if (! $response->successful()) {
            Log::warning('Google Ads search failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Google Ads API failed HTTP '.$response->status());
        }

        $results = $response->json('results');

        return is_array($results) ? $results : [];
    }
}
