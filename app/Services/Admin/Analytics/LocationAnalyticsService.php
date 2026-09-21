<?php

namespace App\Services\Admin\Analytics;

use App\Models\City;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LocationAnalyticsService
{
    use AnalyticsHelper;

    /**
     * Dashboard cards (Riyadh, Jeddah, Eastern, Dammam) — backward compatible shape.
     *
     * @return list<array<string, mixed>>
     */
    public function getLocationAnalytics(): array
    {
        return $this->getDashboardCards('total');
    }

    /**
     * UI cards: total paid per location with % change vs previous month.
     *
     * @return list<array<string, mixed>>
     */
    public function getDashboardCards(string $period = 'total'): array
    {
        [$currentStart, $currentEnd] = $this->periodRange($period);
        [$previousStart, $previousEnd] = $this->previousPeriodRange($period);

        $cards = [];
        foreach (config('location_analytics_cards', []) as $definition) {
            $cards[] = $this->buildCardFromDefinition(
                $definition,
                $currentStart,
                $currentEnd,
                $previousStart,
                $previousEnd
            );
        }

        return $cards;
    }

    /**
     * All cities that have successful payments (via contract uuid → city).
     *
     * @return list<array<string, mixed>>
     */
    public function getAllCitiesPaidTotals(string $period = 'total'): array
    {
        [$start, $end] = $this->periodRange($period);

        $rows = $this->successfulPaymentsQuery($start, $end)
            ->selectRaw('COALESCE(contracts.property_city_id, real_estates.property_city_id) as city_id')
            ->selectRaw('SUM(payments.amount) as total_paid_amount')
            ->selectRaw('COUNT(DISTINCT payments.id) as payments_count')
            ->selectRaw('COUNT(DISTINCT contracts.id) as contracts_count')
            ->whereNotNull(DB::raw('COALESCE(contracts.property_city_id, real_estates.property_city_id)'))
            ->groupBy('city_id')
            ->orderByDesc('total_paid_amount')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $cities = City::query()
            ->with('regions')
            ->whereIn('id', $rows->pluck('city_id')->filter())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($cities) {
            $city = $cities->get((int) $row->city_id);

            return [
                'city_id' => (int) $row->city_id,
                'city_name_ar' => $city?->name_ar,
                'city_name_en' => $city?->name_en,
                'city_name' => $city?->name_trans ?? $city?->name_ar,
                'region_id' => $city?->region_id,
                'region_name_ar' => $city?->regions?->name_ar,
                'total_paid_amount' => round((float) $row->total_paid_amount, 2),
                'payments_count' => (int) $row->payments_count,
                'contracts_count' => (int) $row->contracts_count,
            ];
        })->values()->all();
    }

    /**
     * @return array{
     *     title_ar: string,
     *     cards: list<array<string, mixed>>,
     *     cities: list<array<string, mixed>>,
     *     period: string,
     *     source: string
     * }
     */
    public function getLocationAnalyticsPayload(string $period = 'total'): array
    {
        return [
            'title_ar' => 'تحليلات المواقع',
            'title_en' => 'Location analytics',
            'period' => $period,
            'source' => 'payments.status=success → payments.contract_uuid = contracts.uuid → city from contracts.property_city_id or real_estates.property_city_id',
            'cards' => $this->getDashboardCards($period),
            'cities' => $this->getAllCitiesPaidTotals($period),
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    protected function buildCardFromDefinition(
        array $definition,
        ?Carbon $currentStart,
        ?Carbon $currentEnd,
        ?Carbon $previousStart,
        ?Carbon $previousEnd
    ): array {
        $locationType = $definition['location_type'] ?? 'city';
        $cityId = null;
        $regionId = null;
        $current = 0.0;
        $previous = 0.0;

        if ($locationType === 'region') {
            $region = Region::query()
                ->where('name_ar', $definition['region_name_ar'] ?? '')
                ->first();
            $regionId = $region?->id;
            if ($regionId) {
                $current = $this->getPaymentSumForRegion((int) $regionId, $currentStart, $currentEnd);
                $previous = $this->getPaymentSumForRegion((int) $regionId, $previousStart, $previousEnd);
            }
        } else {
            $city = City::query()
                ->where('name_ar', $definition['city_name_ar'] ?? '')
                ->first();
            $cityId = $city?->id;
            $regionId = $city?->region_id;
            if ($cityId) {
                $current = $this->getPaymentSumForCity((int) $cityId, $currentStart, $currentEnd);
                $previous = $this->getPaymentSumForCity((int) $cityId, $previousStart, $previousEnd);
            }
        }

        $totalPaid = round($current, 2);

        return [
            'key' => $definition['key'],
            'label_ar' => $definition['label_ar'],
            'label_en' => $definition['label_en'] ?? $definition['label_ar'],
            'location_type' => $locationType,
            'city_id' => $cityId,
            'region_id' => $regionId,
            'total_paid_amount' => $totalPaid,
            'value' => $totalPaid,
            'percentage_change' => $this->calculatePercentageChange($current, $previous),
            'type' => 'currency',
            'currency' => 'SAR',
        ];
    }

    protected function getPaymentSumForCity(
        int $cityId,
        ?Carbon $start = null,
        ?Carbon $end = null
    ): float {
        return (float) $this->successfulPaymentsQuery($start, $end)
            ->whereRaw(
                'COALESCE(contracts.property_city_id, real_estates.property_city_id) = ?',
                [$cityId]
            )
            ->sum('payments.amount');
    }

    protected function getPaymentSumForRegion(
        int $regionId,
        ?Carbon $start = null,
        ?Carbon $end = null
    ): float {
        return (float) $this->successfulPaymentsQuery($start, $end)
            ->join('cities as payment_cities', function ($join) {
                $join->on(
                    DB::raw('COALESCE(contracts.property_city_id, real_estates.property_city_id)'),
                    '=',
                    'payment_cities.id'
                );
            })
            ->where('payment_cities.region_id', $regionId)
            ->sum('payments.amount');
    }

    protected function successfulPaymentsQuery(?Carbon $start = null, ?Carbon $end = null): Builder
    {
        $query = DB::table('payments')
            ->join('contracts', 'payments.contract_uuid', '=', 'contracts.uuid')
            ->leftJoin('real_estates', 'contracts.real_id', '=', 'real_estates.id')
            ->where('payments.status', 'success')
            ->where('contracts.is_delete', 0);

        if ($start !== null && $end !== null) {
            $query->whereBetween('payments.created_at', [$start, $end]);
        }

        return $query;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function periodRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today', 'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [null, null],
        };
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function previousPeriodRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today', 'day' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'week' => [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ],
            'month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ],
            'year' => [
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->endOfYear(),
            ],
            default => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ],
        };
    }
}
