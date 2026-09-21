<?php

namespace App\Modules\Analytics\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAnalyticsMetric;
use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\Admin\Analytics\UserAnalyticsService;
use Illuminate\Http\Request;
use Throwable;

class UserDashboardAnalyticsController extends Controller
{
    use ResolvesAnalyticsMetric;
    use Responser;

    public function __construct(
        protected UserAnalyticsService $userAnalytics
    ) {}

    public function userActivityRate()
    {
        try {
            $activity = $this->userAnalytics->getUserActivityRate();

            return $this->analyticsMetric('user_activity_rate', $activity['rate'], [], [
                'active_users_count' => $activity['active_users_count'],
                'total_users_count' => $activity['total_users_count'],
            ]);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function topCustomersCompletedOrders(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);

            return $this->analyticsClientsMetric(
                'most_clients_completed_requests',
                $this->userAnalytics->countUsersWithCompletedOrders(),
                $this->userAnalytics->getTopCustomersByCompletedOrders($limit)
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function topCustomersIncompleteOrders(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);

            return $this->analyticsClientsMetric(
                'most_clients_incomplete_requests',
                $this->userAnalytics->countUsersWithIncompleteOrders(),
                $this->userAnalytics->getTopCustomersByIncompleteOrders($limit)
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function topCustomersOrders(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);

            return $this->analyticsClientsMetric(
                'most_clients_requests',
                $this->userAnalytics->countUsersWithOrders(),
                $this->userAnalytics->getTopCustomersByTotalOrders($limit)
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function topCustomersReturns(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);

            return $this->analyticsClientsMetric(
                'most_clients_returns',
                $this->userAnalytics->getClientsWithRefundsCount(),
                $this->userAnalytics->getTopCustomersByRefunds($limit)
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function topCustomersRealEstates(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);

            return $this->analyticsClientsMetric(
                'most_clients_real_estate',
                $this->userAnalytics->countUsersWithRealEstates(),
                $this->userAnalytics->getTopCustomersByRealEstates($limit)
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function topCustomersUnits(Request $request)
    {
        try {
            $limit = $this->analyticsListLimit($request);

            return $this->analyticsClientsMetric(
                'most_clients_units',
                $this->userAnalytics->countUsersWithUnits(),
                $this->userAnalytics->getTopCustomersByUnits($limit)
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

}
