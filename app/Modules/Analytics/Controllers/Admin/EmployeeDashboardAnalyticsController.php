<?php

namespace App\Modules\Analytics\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAnalyticsMetric;
use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\Admin\Analytics\EmployeeAnalyticsService;
use Illuminate\Http\Request;
use Throwable;

class EmployeeDashboardAnalyticsController extends Controller
{
    use ResolvesAnalyticsMetric;
    use Responser;

    private const METRICS_CONFIG = 'employee_analytics_metrics';

    public function __construct(
        protected EmployeeAnalyticsService $employeeAnalytics
    ) {}

    public function mostReceivedOrders(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);
            $employees = $this->employeeAnalytics->getTopEmployeesByReceivedContracts($limit);

            return $this->analyticsEmployeesMetric(
                'most_received_orders_employee',
                $employees[0]['metric_value'] ?? 0,
                $employees,
                null,
                self::METRICS_CONFIG
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function mostReturns(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);
            $employees = $this->employeeAnalytics->getTopEmployeesByRefundSubmissions($limit);

            return $this->analyticsEmployeesMetric(
                'most_returns_employee',
                $employees[0]['metric_value'] ?? 0,
                $employees,
                [
                    'total_refunds_amount' => $this->employeeAnalytics->getRefundableSum(),
                    'top_employee_name' => $this->topEmployeeDisplayValue($employees),
                ],
                self::METRICS_CONFIG
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function mostDocumentedOrders(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);
            $employees = $this->employeeAnalytics->getTopEmployeesByConfirmedContracts($limit);

            return $this->analyticsEmployeesMetric(
                'most_documented_orders_employee',
                $employees[0]['metric_value'] ?? 0,
                $employees,
                null,
                self::METRICS_CONFIG
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function totalCount()
    {
        try {
            return $this->analyticsMetric(
                'total_employees_count',
                $this->employeeAnalytics->getEmployeeCount(),
                [],
                null,
                self::METRICS_CONFIG
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function mostUnpaidOrders(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);
            $employees = $this->employeeAnalytics->getTopEmployeesByUnpaidOrders($limit);

            return $this->analyticsEmployeesMetric(
                'most_unpaid_orders_employee',
                $employees[0]['metric_value'] ?? 0,
                $employees,
                null,
                self::METRICS_CONFIG
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
