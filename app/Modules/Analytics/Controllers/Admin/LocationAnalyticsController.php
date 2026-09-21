<?php

namespace App\Modules\Analytics\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\Admin\Analytics\LocationAnalyticsService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class LocationAnalyticsController extends Controller
{
    use Responser;

    private const ALLOWED_PERIODS = ['today', 'day', 'week', 'month', 'year', 'total'];

    public function __construct(
        protected LocationAnalyticsService $locationAnalytics
    ) {}

    /**
     * Location analytics: money paid per city (payment → contract uuid → city).
     *
     * GET /api/admin/analytics/locations
     * GET /api/admin/analytics/locations?period=month
     */
    public function index(Request $request)
    {
        try {
            $period = $this->resolvePeriod($request->query('period', 'total'));

            return $this->apiResponse(
                $this->locationAnalytics->getLocationAnalyticsPayload($period),
                trans('api.success')
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * All cities with paid totals only.
     *
     * GET /api/admin/analytics/locations/cities
     */
    public function cities(Request $request)
    {
        try {
            $period = $this->resolvePeriod($request->query('period', 'total'));

            return $this->apiResponse([
                'period' => $period,
                'cities' => $this->locationAnalytics->getAllCitiesPaidTotals($period),
            ], trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function resolvePeriod(?string $period): string
    {
        $period = strtolower(trim((string) ($period ?? 'total')));

        if (! in_array($period, self::ALLOWED_PERIODS, true)) {
            throw new InvalidArgumentException(
                'period must be one of: '.implode(', ', self::ALLOWED_PERIODS)
            );
        }

        return $period === 'day' ? 'today' : $period;
    }
}
