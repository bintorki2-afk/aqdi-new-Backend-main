<?php

namespace App\Services\Admin\Analytics;

use App\Models\RealEstate;
use App\Models\UnitsReal;
use Carbon\Carbon;

class RealEstateAnalyticsService
{
    use AnalyticsHelper;

    public function getRealEstatesAnalytics(): array
    {
        return [
            'today' => ['count' => $this->getRealEstatesForDay(), 'percentage_change' => $this->calculatePercentageChange($this->getRealEstatesForDay(), $this->getRealEstatesForYesterday())],
            'week' => ['count' => $this->getRealEstatesForWeek(), 'percentage_change' => $this->calculatePercentageChange($this->getRealEstatesForWeek(), $this->getRealEstatesForLastWeek())],
            'month' => ['count' => $this->getRealEstatesForMonth(), 'percentage_change' => $this->calculatePercentageChange($this->getRealEstatesForMonth(), $this->getRealEstatesForLastMonth())],
            'year' => ['count' => $this->getRealEstatesForYear(), 'percentage_change' => $this->calculatePercentageChange($this->getRealEstatesForYear(), $this->getRealEstatesForLastYear())],
            'total' => RealEstate::count(),
        ];
    }

    public function getUnitsAnalytics(): array
    {
        return [
            'today' => ['count' => $this->getUnitsForDay(), 'percentage_change' => $this->calculatePercentageChange($this->getUnitsForDay(), $this->getUnitsForYesterday())],
            'week' => ['count' => $this->getUnitsForWeek(), 'percentage_change' => $this->calculatePercentageChange($this->getUnitsForWeek(), $this->getUnitsForLastWeek())],
            'month' => ['count' => $this->getUnitsForMonth(), 'percentage_change' => $this->calculatePercentageChange($this->getUnitsForMonth(), $this->getUnitsForLastMonth())],
            'year' => ['count' => $this->getUnitsForYear(), 'percentage_change' => $this->calculatePercentageChange($this->getUnitsForYear(), $this->getUnitsForLastYear())],
            'total' => UnitsReal::count(),
        ];
    }

    private function getRealEstatesForDay(): int { return RealEstate::whereDate('created_at', Carbon::today())->count(); }
    private function getRealEstatesForYesterday(): int { return RealEstate::whereDate('created_at', Carbon::yesterday())->count(); }
    private function getRealEstatesForWeek(): int { return RealEstate::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count(); }
    private function getRealEstatesForLastWeek(): int { return RealEstate::whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->count(); }
    private function getRealEstatesForMonth(): int { return RealEstate::whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->count(); }
    private function getRealEstatesForLastMonth(): int { return RealEstate::whereMonth('created_at', Carbon::now()->subMonth()->month)->whereYear('created_at', Carbon::now()->subMonth()->year)->count(); }
    private function getRealEstatesForYear(): int { return RealEstate::whereYear('created_at', Carbon::now()->year)->count(); }
    private function getRealEstatesForLastYear(): int { return RealEstate::whereYear('created_at', Carbon::now()->subYear()->year)->count(); }

    private function getUnitsForDay(): int { return UnitsReal::whereDate('created_at', Carbon::today())->count(); }
    private function getUnitsForYesterday(): int { return UnitsReal::whereDate('created_at', Carbon::yesterday())->count(); }
    private function getUnitsForWeek(): int { return UnitsReal::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count(); }
    private function getUnitsForLastWeek(): int { return UnitsReal::whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->count(); }
    private function getUnitsForMonth(): int { return UnitsReal::whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->count(); }
    private function getUnitsForLastMonth(): int { return UnitsReal::whereMonth('created_at', Carbon::now()->subMonth()->month)->whereYear('created_at', Carbon::now()->subMonth()->year)->count(); }
    private function getUnitsForYear(): int { return UnitsReal::whereYear('created_at', Carbon::now()->year)->count(); }
    private function getUnitsForLastYear(): int { return UnitsReal::whereYear('created_at', Carbon::now()->subYear()->year)->count(); }
}
