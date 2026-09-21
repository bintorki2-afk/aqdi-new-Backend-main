<?php

namespace App\Modules\Analytics\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\Admin\DashboardAnalyticsService;

class HomeAdminController extends Controller
{
    use Responser;

    public function __construct(
        protected DashboardAnalyticsService $analytics
    ) {}

    /**
     * Get comprehensive dashboard analytics
     */
    public function analysis()
    {
        return $this->apiResponse(
            $this->analytics->getFullAnalysis(),
            trans('api.success')
        );
    }

    public function getFinancialAnalytics()
    {
        return $this->analytics->getFinancialAnalyticsData();
    }

    public function getUserAnalytics()
    {
        return $this->analytics->getUserAnalyticsData();
    }
}
