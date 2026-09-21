<?php

namespace App\Services\Admin;

use App\Models\Blog;
use App\Models\City;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\RealEstate;
use App\Models\RefundableContract;
use App\Models\UnitsReal;
use App\Models\User;
use App\Models\Visitor;
use App\Services\Admin\Analytics\CityAnalyticsService;
use App\Services\Admin\Analytics\EmployeeAnalyticsService;
use App\Services\Admin\Analytics\FinancialAnalyticsService;
use App\Services\Admin\Analytics\LocationAnalyticsService;
use App\Services\Admin\Analytics\OrderAnalyticsService;
use App\Services\Admin\Analytics\RealEstateAnalyticsService;
use App\Services\Admin\Analytics\UserAnalyticsService;

class DashboardAnalyticsService
{
    public function __construct(
        protected FinancialAnalyticsService $financialAnalytics,
        protected UserAnalyticsService $userAnalytics,
        protected OrderAnalyticsService $orderAnalytics,
        protected EmployeeAnalyticsService $employeeAnalytics,
        protected LocationAnalyticsService $locationAnalytics,
        protected RealEstateAnalyticsService $realEstateAnalytics,
        protected CityAnalyticsService $cityAnalytics,
    ) {}

    public function getFullAnalysis(): array
    {
        return [
            'control_panel' => $this->getControlPanel(),
            'financial_analytics' => $this->getFinancialAnalytics(),
            'user_analytics' => $this->getUserAnalytics(),
            'order_analytics' => $this->orderAnalytics->getOrderAnalyticsData(),
            'top_customers' => $this->userAnalytics->getTopCustomers(),
            'inactive_users' => $this->userAnalytics->getInactiveUsers(),
            'employee_analytics' => $this->getEmployeeAnalytics(),
            'real_estate_and_units_analytics' => $this->getRealEstateAndUnitsAnalytics(),
            'location_analytics' => $this->locationAnalytics->getLocationAnalytics(),
            'order_transfer_analytics' => $this->orderAnalytics->getOrderTransferAnalytics(),
            'cities' => $this->cityAnalytics->getMostPopularCity(),
        ];
    }

    public function getFinancialAnalyticsData(): array
    {
        return [
            'income' => $this->financialAnalytics->getIncomeAnalytics(),
            'completed_orders' => $this->financialAnalytics->getCompletedOrdersAnalytics(),
            'incomplete_orders' => $this->financialAnalytics->getIncompleteOrdersAnalytics(),
            'refunds' => $this->financialAnalytics->getRefundsAnalytics(),
            'expenses' => $this->financialAnalytics->getExpensesAnalytics(),
            'cities' => $this->cityAnalytics->getMostPopularCity(),
        ];
    }

    public function getUserAnalyticsData(): array
    {
        return [
            'new_users' => $this->userAnalytics->getNewUsersAnalytics(),
            'user_activity_rate' => $this->userAnalytics->getUserActivityRate(),
            'inactive_users' => $this->userAnalytics->getInactiveUsers(),
            'top_customers' => $this->userAnalytics->getTopCustomers(),
        ];
    }

    private function getControlPanel(): array
    {
        return [
            ['label_ar' => 'العقود الغير المكتملة', 'value' => Contract::where('is_completed', 0)->where('is_delete', 0)->count(), 'type' => 'count'],
            ['label_ar' => 'العقود المكتملة', 'value' => Contract::where('is_completed', 1)->where('is_delete', 0)->count(), 'type' => 'count'],
            ['label_ar' => 'المستخدمين', 'value' => User::count(), 'type' => 'count'],
            ['label_ar' => 'الزيارات', 'value' => Visitor::count(), 'type' => 'count'],
            ['label_ar' => 'المقالات', 'value' => Blog::count(), 'type' => 'count'],
            ['label_ar' => 'الموظفين', 'value' => Employee::count(), 'type' => 'count'],
            ['label_ar' => 'الوحدات', 'value' => UnitsReal::count(), 'type' => 'count'],
            ['label_ar' => 'المدن', 'value' => City::count(), 'type' => 'count'],
            // "عقود مدفوعه" is a COUNT of paid contracts (was mistakenly a second copy of the
            // payments-sum, which double-counted revenue when the two currency tiles were added up).
            ['label_ar' => 'عقود مدفوعه', 'value' => Contract::where('is_completed', 1)->where('is_delete', 0)->count(), 'type' => 'count'],
            ['label_ar' => 'اجمالي المدفوعات في العقود', 'value' => Payment::where('status', 'success')->sum('amount'), 'type' => 'currency'],
        ];
    }

    private function getFinancialAnalytics(): array
    {
        $financial = $this->financialAnalytics;
        return [
            'income' => $financial->formatFinancialRow($financial->getIncomeAnalytics(), 'amount', [
                'today' => 'دخل اليوم', 'week' => 'دخل الأسبوع', 'month' => 'دخل الشهر', 'year' => 'دخل العام', 'total' => 'إجمالي الدخل'
            ]),
            'completed_orders' => $financial->formatFinancialRow($financial->getCompletedOrdersAnalytics(), 'count', [
                'today' => 'طلبات اليوم المكتمله', 'week' => 'طلبات الأسبوع المكتمله', 'month' => 'طلبات الشهر المكتمله', 'year' => 'طلبات السنة المكتمله', 'total' => 'إجمالي الطلبات المكتمله'
            ]),
            'incomplete_orders' => $financial->formatFinancialRow($financial->getIncompleteOrdersAnalytics(), 'count', [
                'today' => 'طلبات اليوم الغير المكتمله', 'week' => 'طلبات الأسبوع الغير المكتمله', 'month' => 'طلبات الشهر الغير المكتمله', 'year' => 'طلبات السنة الغير المكتمله', 'total' => 'إجمالي الطلبات الغير المكتمله'
            ]),
            'refunds' => $financial->formatFinancialRow($financial->getRefundsAnalytics(), 'amount', [
                'today' => 'مسترجع اليوم', 'week' => 'مسترجع الأسبوع', 'month' => 'مسترجع الشهر', 'year' => 'مسترجع العام', 'total' => 'إجمالي المسترجع'
            ]),
            'expenses' => $financial->formatFinancialRow($financial->getExpensesAnalytics(), 'amount', [
                'today' => 'مصروفات اليوم', 'week' => 'مصروفات الأسبوع', 'month' => 'مصروفات الشهر', 'year' => 'مصروفات العام', 'total' => 'إجمالي المصروفات'
            ]),
        ];
    }

    private function getUserAnalytics(): array
    {
        $user = $this->userAnalytics;
        $activityRate = $user->getUserActivityRate();
        return [
            'new_users' => $user->formatFinancialRow($user->getNewUsersAnalytics(), 'count', [
                'today' => 'المستخدمين الجدد / اليوم', 'week' => 'المستخدمين الجدد / الأسبوع', 'month' => 'المستخدمين الجدد / الشهر', 'year' => 'المستخدمين الجدد / العام', 'total' => 'الإجمالي'
            ]),
            'user_activity_rate' => [
                'label_ar' => 'معدل نشاط المستخدمين',
                'value' => $activityRate['rate'],
                'type' => 'percentage',
            ],
            'most_clients_completed_requests' => [
                'label_ar' => 'اكثر العملاء طلب مكتمل',
                'value' => User::whereHas('contracts', fn ($q) => $q->where('is_completed', 1)->where('is_delete', 0))->count(),
                'type' => 'count',
            ],
            'most_clients_incomplete_requests' => [
                'label_ar' => 'اكثر العملاء طلب غير مكتمل',
                'value' => User::whereHas('contracts', fn ($q) => $q->where('is_completed', 0)->where('is_delete', 0))->count(),
                'type' => 'count',
            ],
            'most_clients_requests' => ['label_ar' => 'اكثر العملاء طلبات', 'value' => User::has('contracts')->count(), 'type' => 'count'],
            'most_clients_returns' => ['label_ar' => 'اكثر العملاء استرجاع', 'value' => $user->getClientsWithRefundsCount(), 'type' => 'count'],
            'most_clients_real_estate' => ['label_ar' => 'اكثر العملاء عقارات', 'value' => User::has('realEstate')->count(), 'type' => 'count'],
            'most_clients_units' => ['label_ar' => 'اكثر العملاء وحدات', 'value' => User::has('unitReal')->count(), 'type' => 'count'],
        ];
    }

    private function getEmployeeAnalytics(): array
    {
        $emp = $this->employeeAnalytics;
        $received = $emp->getTopEmployeesByReceivedContracts(1);
        $documented = $emp->getTopEmployeesByConfirmedContracts(1);
        $unpaid = $emp->getTopEmployeesByUnpaidOrders(1);

        return [
            ['label_ar' => 'أكثر الموظفين استلم طلب', 'value' => $received[0]['name'] ?? null, 'type' => 'list'],
            ['label_ar' => 'أكثر الموظفين قدم استرجاع', 'value' => $emp->getRefundableSum(), 'type' => 'currency'],
            ['label_ar' => 'أكثر الموظفين وثق طلب', 'value' => $documented[0]['name'] ?? null, 'type' => 'list'],
            ['label_ar' => 'عدد الموظفين', 'value' => $emp->getEmployeeCount(), 'type' => 'count'],
            ['label_ar' => 'أكثر الموظفين اكتسب طلب غير مدفوع', 'value' => $unpaid[0]['name'] ?? null, 'type' => 'list'],
        ];
    }

    private function getRealEstateAndUnitsAnalytics(): array
    {
        $re = $this->realEstateAnalytics;
        return [
            'real_estates' => $re->formatFinancialRow($re->getRealEstatesAnalytics(), 'count', [
                'today' => 'عقارات المضافة / اليوم', 'week' => 'عقارات المضافة / الأسبوع', 'month' => 'عقارات المضافة / الشهر', 'year' => 'عقارات المضافة / العام', 'total' => 'الإجمالي'
            ]),
            'units' => $re->formatFinancialRow($re->getUnitsAnalytics(), 'count', [
                'today' => 'وحدات مضافة / اليوم', 'week' => 'وحدات مضافة / الأسبوع', 'month' => 'وحدات مضافة / الشهر', 'year' => 'وحدات مضافة / العام', 'total' => 'الإجمالي'
            ]),
        ];
    }
}
