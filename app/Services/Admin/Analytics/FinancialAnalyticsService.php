<?php

namespace App\Services\Admin\Analytics;

use App\Models\Contract;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;

class FinancialAnalyticsService
{
    use AnalyticsHelper;

    public function getIncomeAnalytics(): array
    {
        $today = Payment::where('status', 'success')->whereDate('created_at', Carbon::today())->sum('amount');
        $yesterday = Payment::where('status', 'success')->whereDate('created_at', Carbon::yesterday())->sum('amount');
        $week = Payment::where('status', 'success')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('amount');
        $lastWeek = Payment::where('status', 'success')
            ->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])
            ->sum('amount');
        $month = Payment::where('status', 'success')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');
        $lastMonth = Payment::where('status', 'success')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('amount');
        $year = Payment::where('status', 'success')->whereYear('created_at', Carbon::now()->year)->sum('amount');
        $lastYear = Payment::where('status', 'success')->whereYear('created_at', Carbon::now()->subYear()->year)->sum('amount');
        $total = Payment::where('status', 'success')->sum('amount');

        return [
            'today' => ['amount' => round($today, 2), 'percentage_change' => $this->calculatePercentageChange($today, $yesterday)],
            'week' => ['amount' => round($week, 2), 'percentage_change' => $this->calculatePercentageChange($week, $lastWeek)],
            'month' => ['amount' => round($month, 2), 'percentage_change' => $this->calculatePercentageChange($month, $lastMonth)],
            'year' => ['amount' => round($year, 2), 'percentage_change' => $this->calculatePercentageChange($year, $lastYear)],
            'total' => round($total, 2),
        ];
    }

    public function getCompletedOrdersAnalytics(): array
    {
        $getCompleted = fn($start, $end) => Contract::where('is_completed', 1)->where('is_delete', 0)
            ->whereBetween('created_at', [$start, $end])->count();

        $today = $getCompleted(Carbon::today(), Carbon::tomorrow());
        $week = $getCompleted(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek());
        $month = $getCompleted(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
        $year = $getCompleted(Carbon::now()->startOfYear(), Carbon::now()->endOfYear());
        $total = Contract::where('is_completed', 1)->where('is_delete', 0)->count();

        return [
            'today' => ['count' => $today, 'percentage_change' => $this->calculatePercentageChange($today, $getCompleted(Carbon::yesterday(), Carbon::today()))],
            'week' => ['count' => $week, 'percentage_change' => $this->calculatePercentageChange($week, $getCompleted(Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()))],
            'month' => ['count' => $month, 'percentage_change' => $this->calculatePercentageChange($month, $getCompleted(Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()))],
            'year' => ['count' => $year, 'percentage_change' => $this->calculatePercentageChange($year, $getCompleted(Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()))],
            'total' => $total,
        ];
    }

    public function getIncompleteOrdersAnalytics(): array
    {
        $getIncomplete = fn($start, $end) => Contract::where('is_completed', 0)->where('is_delete', 0)
            ->whereBetween('created_at', [$start, $end])->count();

        $today = $getIncomplete(Carbon::today(), Carbon::tomorrow());
        $week = $getIncomplete(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek());
        $month = $getIncomplete(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
        $year = $getIncomplete(Carbon::now()->startOfYear(), Carbon::now()->endOfYear());
        $total = Contract::where('is_completed', 0)->where('is_delete', 0)->count();

        return [
            'today' => ['count' => $today, 'percentage_change' => $this->calculatePercentageChange($today, $getIncomplete(Carbon::yesterday(), Carbon::today()))],
            'week' => ['count' => $week, 'percentage_change' => $this->calculatePercentageChange($week, $getIncomplete(Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()))],
            'month' => ['count' => $month, 'percentage_change' => $this->calculatePercentageChange($month, $getIncomplete(Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()))],
            'year' => ['count' => $year, 'percentage_change' => $this->calculatePercentageChange($year, $getIncomplete(Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()))],
            'total' => $total,
        ];
    }

    public function getRefundsAnalytics(): array
    {
        $base = fn() => Payment::where('status', 'failed');
        $today = $base()->whereDate('created_at', Carbon::today())->sum('amount');
        $yesterday = $base()->whereDate('created_at', Carbon::yesterday())->sum('amount');
        $week = $base()->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('amount');
        $lastWeek = $base()->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->sum('amount');
        $month = $base()->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->sum('amount');
        $lastMonth = $base()->whereMonth('created_at', Carbon::now()->subMonth()->month)->whereYear('created_at', Carbon::now()->subMonth()->year)->sum('amount');
        $year = $base()->whereYear('created_at', Carbon::now()->year)->sum('amount');
        $lastYear = $base()->whereYear('created_at', Carbon::now()->subYear()->year)->sum('amount');
        $total = Payment::where('status', 'failed')->sum('amount');

        return [
            'today' => ['amount' => round($today, 2), 'percentage_change' => $this->calculatePercentageChange($today, $yesterday)],
            'week' => ['amount' => round($week, 2), 'percentage_change' => $this->calculatePercentageChange($week, $lastWeek)],
            'month' => ['amount' => round($month, 2), 'percentage_change' => $this->calculatePercentageChange($month, $lastMonth)],
            'year' => ['amount' => round($year, 2), 'percentage_change' => $this->calculatePercentageChange($year, $lastYear)],
            'total' => round($total, 2),
        ];
    }

    public function getExpensesAnalytics(): array
    {
        $today = Expense::whereDate('created_at', Carbon::today())->sum('amount');
        $yesterday = Expense::whereDate('created_at', Carbon::yesterday())->sum('amount');
        $week = Expense::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('amount');
        $lastWeek = Expense::whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->sum('amount');
        $month = Expense::whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->sum('amount');
        $lastMonth = Expense::whereMonth('created_at', Carbon::now()->subMonth()->month)->whereYear('created_at', Carbon::now()->subMonth()->year)->sum('amount');
        $year = Expense::whereYear('created_at', Carbon::now()->year)->sum('amount');
        $lastYear = Expense::whereYear('created_at', Carbon::now()->subYear()->year)->sum('amount');
        $total = Expense::sum('amount');

        return [
            'today' => ['amount' => round($today, 2), 'percentage_change' => $this->calculatePercentageChange($today, $yesterday)],
            'week' => ['amount' => round($week, 2), 'percentage_change' => $this->calculatePercentageChange($week, $lastWeek)],
            'month' => ['amount' => round($month, 2), 'percentage_change' => $this->calculatePercentageChange($month, $lastMonth)],
            'year' => ['amount' => round($year, 2), 'percentage_change' => $this->calculatePercentageChange($year, $lastYear)],
            'total' => round($total, 2),
        ];
    }
}
