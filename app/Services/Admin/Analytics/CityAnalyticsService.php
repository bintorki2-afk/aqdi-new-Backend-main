<?php

namespace App\Services\Admin\Analytics;

use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CityAnalyticsService
{
    public function getMostPopularCity(): array
    {
        return [
            'day' => $this->getMostPopularCityForDay(),
            'week' => $this->getMostPopularCityForWeek(),
            'month' => $this->getMostPopularCityForMonth(),
            'year' => $this->getMostPopularCityForYear(),
        ];
    }

    private function getMostPopularCityForDay(): array
    {
        $result = Contract::select('property_city_id', DB::raw('COUNT(*) as count'))
            ->whereDate('created_at', Carbon::today())
            ->groupBy('property_city_id')
            ->orderByDesc('count')
            ->with('propertyCity')
            ->first();
        return [
            'city_id' => $result?->property_city_id,
            'city_name' => $result?->propertyCity?->name_trans ?? $result?->propertyCity?->name_ar,
            'contracts_count' => $result?->count ?? 0,
        ];
    }

    private function getMostPopularCityForWeek(): array
    {
        $result = Contract::select('property_city_id', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->groupBy('property_city_id')
            ->orderByDesc('count')
            ->with('propertyCity')
            ->first();
        return [
            'city_id' => $result?->property_city_id,
            'city_name' => $result?->propertyCity?->name_trans ?? $result?->propertyCity?->name_ar,
            'contracts_count' => $result?->count ?? 0,
        ];
    }

    private function getMostPopularCityForMonth(): array
    {
        $result = Contract::select('property_city_id', DB::raw('COUNT(*) as count'))
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('property_city_id')
            ->orderByDesc('count')
            ->with('propertyCity')
            ->first();
        return [
            'city_id' => $result?->property_city_id,
            'city_name' => $result?->propertyCity?->name_trans ?? $result?->propertyCity?->name_ar,
            'contracts_count' => $result?->count ?? 0,
        ];
    }

    private function getMostPopularCityForYear(): array
    {
        $result = Contract::select('property_city_id', DB::raw('COUNT(*) as count'))
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('property_city_id')
            ->orderByDesc('count')
            ->with('propertyCity')
            ->first();
        return [
            'city_id' => $result?->property_city_id,
            'city_name' => $result?->propertyCity?->name_trans ?? $result?->propertyCity?->name_ar,
            'contracts_count' => $result?->count ?? 0,
        ];
    }
}
