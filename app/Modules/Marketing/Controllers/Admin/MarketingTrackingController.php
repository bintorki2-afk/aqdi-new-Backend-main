<?php

namespace App\Modules\Marketing\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\Admin\ReportsService;
use App\Services\Marketing\Tracking\MarketingChannelTrackingService;
use App\Services\Marketing\Tracking\MarketingKeywordTrackingService;
use App\Services\Marketing\Tracking\MarketingTrackingOverviewService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class MarketingTrackingController extends Controller
{
    use Responser;

    public function __construct(
        protected ReportsService $reports,
        protected MarketingTrackingOverviewService $overview,
        protected MarketingKeywordTrackingService $keywords,
        protected MarketingChannelTrackingService $channels,
    ) {}

    public function overview(Request $request)
    {
        return $this->respond($request, fn (array $filter) => $this->overview->dashboard($filter));
    }

    public function keywords(Request $request)
    {
        return $this->respond($request, fn (array $filter) => $this->keywords->dashboard($filter));
    }

    public function channels(Request $request)
    {
        return $this->respond($request, fn (array $filter) => $this->channels->dashboard($filter));
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $callback
     */
    protected function respond(Request $request, callable $callback)
    {
        try {
            $filter = $this->reports->resolveReportPeriodFilter($request);

            return $this->apiResponse(array_merge([
                'periods' => $this->reports->reportPeriodTabs($filter['key']),
                'period' => $filter['key'],
                'date_from' => $filter['date_from'],
                'date_to' => $filter['date_to'],
            ], $callback($filter)), trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
