<?php

namespace App\Modules\Marketing\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Services\Admin\ReportsService;
use App\Services\Marketing\Content\MarketingArticlesService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class MarketingArticlesController extends Controller
{
    use Responser;

    public function __construct(
        protected ReportsService $reports,
        protected MarketingArticlesService $articles,
    ) {}

    public function index(Request $request)
    {
        try {
            $filter = $this->reports->resolveReportPeriodFilter($request);
            $category = $request->filled('category') ? (string) $request->input('category') : null;
            $status = $request->filled('status') ? strtolower((string) $request->input('status')) : null;
            if ($status === 'schedule') {
                $status = 'scheduled';
            }

            return $this->apiResponse(array_merge([
                'periods' => $this->reports->reportPeriodTabs($filter['key']),
                'period' => $filter['key'],
                'date_from' => $filter['date_from'],
                'date_to' => $filter['date_to'],
                'currency' => 'SAR',
                'currency_label_ar' => 'ريال',
            ], $this->articles->dashboard($filter, $category, $status)), trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
