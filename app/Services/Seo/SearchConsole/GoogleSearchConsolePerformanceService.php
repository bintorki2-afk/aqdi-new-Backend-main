<?php

namespace App\Services\Seo\SearchConsole;

class GoogleSearchConsolePerformanceService
{
    public const DIMENSION_QUERY = 'query';

    public const DIMENSION_PAGE = 'page';

    public const DIMENSION_COUNTRY = 'country';

    public const DIMENSION_DEVICE = 'device';

    public const DIMENSION_DATE = 'date';

    public function __construct(
        protected GoogleSearchConsoleClient $client,
        protected GoogleSearchConsoleSiteService $sites,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(?string $from = null, ?string $to = null): array
    {
        $period = SearchConsolePeriod::fromInput($from, $to);
        $siteUrl = $this->sites->siteUrl();
        $payload = $this->client->querySearchAnalytics($siteUrl, [
            'startDate' => $period->from,
            'endDate' => $period->to,
            'dataState' => 'all',
        ]);
        $row = is_array($payload['rows'][0] ?? null) ? $payload['rows'][0] : [];

        return [
            'site_url' => $siteUrl,
            'from' => $period->from,
            'to' => $period->to,
            'clicks' => (int) ($row['clicks'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'ctr' => round((float) ($row['ctr'] ?? 0), 4),
            'position' => round((float) ($row['position'] ?? 0), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function queries(?string $from = null, ?string $to = null, int $limit = 25, int $startRow = 0): array
    {
        return $this->dimensionReport(self::DIMENSION_QUERY, $from, $to, $limit, $startRow);
    }

    /**
     * @return array<string, mixed>
     */
    public function pages(?string $from = null, ?string $to = null, int $limit = 25, int $startRow = 0): array
    {
        return $this->dimensionReport(self::DIMENSION_PAGE, $from, $to, $limit, $startRow);
    }

    /**
     * @return array<string, mixed>
     */
    public function countries(?string $from = null, ?string $to = null, int $limit = 25, int $startRow = 0): array
    {
        return $this->dimensionReport(self::DIMENSION_COUNTRY, $from, $to, $limit, $startRow);
    }

    /**
     * @return array<string, mixed>
     */
    public function devices(?string $from = null, ?string $to = null, int $limit = 25, int $startRow = 0): array
    {
        return $this->dimensionReport(self::DIMENSION_DEVICE, $from, $to, $limit, $startRow);
    }

    /**
     * @return array<string, mixed>
     */
    public function dates(?string $from = null, ?string $to = null, int $limit = 500, int $startRow = 0): array
    {
        return $this->dimensionReport(self::DIMENSION_DATE, $from, $to, $limit, $startRow);
    }

    /**
     * @return array<string, mixed>
     */
    protected function dimensionReport(
        string $dimension,
        ?string $from,
        ?string $to,
        int $limit,
        int $startRow,
    ): array {
        $period = SearchConsolePeriod::fromInput($from, $to);
        $siteUrl = $this->sites->siteUrl();
        $limit = max(1, min($limit, 1000));
        $startRow = max(0, $startRow);

        $payload = $this->client->querySearchAnalytics($siteUrl, [
            'startDate' => $period->from,
            'endDate' => $period->to,
            'dimensions' => [$dimension],
            'rowLimit' => $limit,
            'startRow' => $startRow,
            'dataState' => 'all',
        ]);

        $items = [];
        foreach ($payload['rows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $items[] = [
                $dimension => (string) ($row['keys'][0] ?? ''),
                'clicks' => (int) ($row['clicks'] ?? 0),
                'impressions' => (int) ($row['impressions'] ?? 0),
                'ctr' => round((float) ($row['ctr'] ?? 0), 4),
                'position' => round((float) ($row['position'] ?? 0), 2),
            ];
        }

        return [
            'site_url' => $siteUrl,
            'from' => $period->from,
            'to' => $period->to,
            'dimension' => $dimension,
            'start_row' => $startRow,
            'limit' => $limit,
            'items' => $items,
        ];
    }
}
