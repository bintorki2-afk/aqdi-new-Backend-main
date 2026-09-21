<?php

namespace App\Services\Admin\Analytics;

use App\Models\Employee;
use App\Models\Payment;
use App\Models\ReceivedContract;
use App\Models\RefundableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeAnalyticsService
{
    public function getEmployeeCount(): int
    {
        return Employee::query()->count();
    }

    public function getRefundableSum(): float
    {
        return (float) RefundableContract::query()->sum('refund_amount');
    }

    public function countEmployeesWithReceivedContracts(): int
    {
        return Employee::query()
            ->has('receivedContract')
            ->count();
    }

    public function countEmployeesWithDocumentedOrders(): int
    {
        return Employee::query()
            ->whereHas('receivedContract.contract', fn ($q) => $q->where('is_completed', 1)->where('is_delete', 0))
            ->count();
    }

    public function countEmployeesWithUnpaidOrders(): int
    {
        $paidUuids = Payment::query()->where('status', 'success')->pluck('contract_uuid');

        return Employee::query()
            ->whereHas('receivedContract', function ($q) use ($paidUuids) {
                $q->whereHas('contract', function ($c) use ($paidUuids) {
                    if ($paidUuids->isNotEmpty()) {
                        $c->whereNotIn('uuid', $paidUuids);
                    }
                });
            })
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopEmployeesByReceivedContracts(int $limit = 10): array
    {
        return $this->fetchTopAnalyticsEmployees(
            fn (Builder $q) => $q->withCount('receivedContract')
                ->having('received_contract_count', '>', 0)
                ->orderByDesc('received_contract_count'),
            'received_contract_count',
            'عدد العقود المستلمة',
            $limit
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopEmployeesByConfirmedContracts(int $limit = 10): array
    {
        return $this->fetchTopAnalyticsEmployees(
            fn (Builder $q) => $q->withCount(['receivedContract as confirmed_count' => function ($query) {
                $query->whereHas('contract', fn ($c) => $c->where('is_completed', 1)->where('is_delete', 0));
            }])
                ->having('confirmed_count', '>', 0)
                ->orderByDesc('confirmed_count'),
            'confirmed_count',
            'عدد العقود الموثقة',
            $limit
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopEmployeesByRefundSubmissions(int $limit = 10): array
    {
        $ids = Employee::query()
            ->select('employees.id')
            ->selectRaw('COUNT(refundable_contracts.id) as refunds_count')
            ->selectRaw('COALESCE(SUM(refundable_contracts.refund_amount), 0) as total_refunds')
            ->join('refundable_contracts', 'employees.id', '=', 'refundable_contracts.employee_id')
            ->groupBy('employees.id')
            ->orderByDesc('refunds_count')
            ->orderByDesc('total_refunds')
            ->limit($limit)
            ->get();

        if ($ids->isEmpty()) {
            return [];
        }

        $employees = Employee::query()
            ->with('roleRelation')
            ->whereIn('id', $ids->pluck('id'))
            ->get()
            ->keyBy('id');

        $ordered = $ids->map(function ($row) use ($employees) {
            $employee = $employees->get($row->id);
            if (! $employee) {
                return null;
            }
            $employee->refunds_count = (int) $row->refunds_count;
            $employee->total_refunds = (float) $row->total_refunds;

            return $employee;
        })->filter();

        return $this->mapAnalyticsEmployees($ordered, 'refunds_count', 'عدد العقود المسترجعة');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopEmployeesByUnpaidOrders(int $limit = 10): array
    {
        $paidUuids = Payment::query()->where('status', 'success')->pluck('contract_uuid')->all();

        $rows = ReceivedContract::query()
            ->select('employee_id', DB::raw('COUNT(*) as unpaid_count'))
            ->whereHas('contract', function ($q) use ($paidUuids) {
                if ($paidUuids !== []) {
                    $q->whereNotIn('uuid', $paidUuids);
                }
            })
            ->groupBy('employee_id')
            ->orderByDesc('unpaid_count')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $employees = Employee::query()
            ->with('roleRelation')
            ->whereIn('id', $rows->pluck('employee_id'))
            ->get()
            ->keyBy('id');

        $ordered = $rows->map(function ($row) use ($employees) {
            $employee = $employees->get($row->employee_id);
            if (! $employee) {
                return null;
            }
            $employee->unpaid_count = (int) $row->unpaid_count;

            return $employee;
        })->filter();

        return $this->mapAnalyticsEmployees($ordered, 'unpaid_count', 'عدد الطلبات غير المدفوعة');
    }

    /**
     * @param  callable(Builder): void  $applySortAndFilter
     * @return array<int, array<string, mixed>>
     */
    protected function fetchTopAnalyticsEmployees(
        callable $applySortAndFilter,
        string $metricField,
        string $metricLabelAr,
        int $limit
    ): array {
        // See UserAnalyticsService::topAnalyticsClientsQuery() — GROUP BY pk keeps HAVING portable.
        $query = Employee::query()->select('employees.*')->groupBy('employees.id')->with('roleRelation');
        $applySortAndFilter($query);

        return $this->mapAnalyticsEmployees($query->limit($limit)->get(), $metricField, $metricLabelAr);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mapAnalyticsEmployees(
        Collection $employees,
        string $metricField,
        string $metricLabelAr
    ): array {
        return $employees->values()->map(function (Employee $employee, int $index) use ($metricField, $metricLabelAr) {
            $metricValue = (int) ($employee->{$metricField} ?? 0);

            return [
                'rank' => $index + 1,
                'id' => $employee->id,
                'name' => $employee->name,
                'role' => $employee->resolvedRoleName(),
                'role_title' => $employee->resolvedRoleTitle(),
                'profile_image' => $employee->profile_image ? url($employee->profile_image) : null,
                'is_active' => (bool) $employee->is_active,
                'metric_value' => $metricValue,
                'metric_count' => $metricValue,
                'metric_label_ar' => $metricLabelAr,
                'count' => $metricValue,
                'total_refunds' => isset($employee->total_refunds)
                    ? round((float) $employee->total_refunds, 2)
                    : null,
            ];
        })->all();
    }
}
