<?php

namespace App\Services\Admin\Analytics;

use App\Models\Contract;
use App\Models\ContractStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ContractStatusAnalyticsService
{
    use AnalyticsHelper;

    public const DEFAULT_STATUS_ID = 2;

    /** Query ?created_at=day|week|month|year|total (aliases: today, daily, weekly, …). */
    public const CREATED_AT_FILTERS = ['day', 'week', 'month', 'year', 'total'];

    /** @var array<string, string> */
    private const CREATED_AT_ALIASES = [
        'day' => 'today',
        'today' => 'today',
        'daily' => 'today',
        'week' => 'week',
        'weekly' => 'week',
        'month' => 'month',
        'monthly' => 'month',
        'year' => 'year',
        'yearly' => 'year',
        'total' => 'total',
        'all' => 'total',
    ];

    public function resolveCreatedAtPeriod(?string $createdAt): ?string
    {
        if ($createdAt === null || trim($createdAt) === '') {
            return null;
        }

        $key = strtolower(trim($createdAt));
        $period = self::CREATED_AT_ALIASES[$key] ?? null;

        if ($period === null) {
            throw new \InvalidArgumentException(
                'created_at must be one of: '.implode(', ', self::CREATED_AT_FILTERS)
            );
        }

        return $period;
    }

    public function metricKeyForPeriod(string $period): string
    {
        return match ($period) {
            'today' => 'contract_status_daily',
            'week' => 'contract_status_weekly',
            'month' => 'contract_status_monthly',
            'year' => 'contract_status_yearly',
            'total' => 'contract_status_total',
            default => throw new \InvalidArgumentException("Unknown period: {$period}"),
        };
    }

    public function createdAtLabelForPeriod(string $period): string
    {
        return match ($period) {
            'today' => 'day',
            'week' => 'week',
            'month' => 'month',
            'year' => 'year',
            'total' => 'total',
            default => $period,
        };
    }

    public function resolveStatus(int $contractStatusId): ContractStatus
    {
        $status = ContractStatus::query()->find($contractStatusId);
        if (! $status) {
            throw new ModelNotFoundException('Contract status not found.');
        }

        return $status;
    }

    /**
     * @return array<string, array{value: int|float, percentage_change: float|null}>
     */
    public function getPeriodMetrics(int $contractStatusId): array
    {
        $countBetween = function ($start, $end) use ($contractStatusId) {
            return $this->baseQuery($contractStatusId)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        };

        $today = $countBetween(Carbon::today(), Carbon::tomorrow());
        $yesterday = $countBetween(Carbon::yesterday(), Carbon::today());
        $week = $countBetween(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek());
        $lastWeek = $countBetween(
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek()
        );
        $month = $countBetween(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
        $lastMonth = $countBetween(
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        );
        $year = $countBetween(Carbon::now()->startOfYear(), Carbon::now()->endOfYear());
        $lastYear = $countBetween(
            Carbon::now()->subYear()->startOfYear(),
            Carbon::now()->subYear()->endOfYear()
        );
        $total = $this->baseQuery($contractStatusId)->count();

        return [
            'today' => [
                'value' => $today,
                'percentage_change' => $this->calculatePercentageChange($today, $yesterday),
            ],
            'week' => [
                'value' => $week,
                'percentage_change' => $this->calculatePercentageChange($week, $lastWeek),
            ],
            'month' => [
                'value' => $month,
                'percentage_change' => $this->calculatePercentageChange($month, $lastMonth),
            ],
            'year' => [
                'value' => $year,
                'percentage_change' => $this->calculatePercentageChange($year, $lastYear),
            ],
            'total' => [
                'value' => $total,
                'percentage_change' => null,
            ],
        ];
    }

    public function getContractsForPeriod(int $contractStatusId, string $period, int $limit = 10): EloquentCollection
    {
        $query = $this->baseQuery($contractStatusId)->with($this->contractOrderRelations());

        $this->applyPeriodFilter($query, $period);

        return $query->latest()->limit($limit)->get();
    }

    protected function applyPeriodFilter($query, string $period): void
    {
        match ($period) {
            'today' => $query->whereDate('created_at', Carbon::today()),
            'week' => $query->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]),
            'month' => $query->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]),
            'year' => $query->whereBetween('created_at', [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ]),
            'total' => null,
            default => throw new \InvalidArgumentException("Unknown period: {$period}"),
        };
    }

    /**
     * @return array<int, string>
     */
    protected function contractOrderRelations(): array
    {
        return [
            'user',
            'receivedContract.employee',
            'contractStatus',
            'contractPayments',
        ];
    }

    protected function baseQuery(int $contractStatusId)
    {
        return Contract::query()
            ->where('contract_status_id', $contractStatusId)
            ->where('is_delete', 0);
    }

    public function paginateContracts(
        int $contractStatusId,
        ?string $period = null,
        int $perPage = 20,
        ?string $search = null
    ): LengthAwarePaginator {
        $this->resolveStatus($contractStatusId);

        $query = $this->baseQuery($contractStatusId)->with($this->contractOrderRelations());

        if ($period) {
            $this->applyPeriodFilter($query, $period);
        }

        if ($search !== null && trim($search) !== '') {
            $query->adminSearch($search);
        }

        return $query->latest()->paginate($perPage);
    }
}
