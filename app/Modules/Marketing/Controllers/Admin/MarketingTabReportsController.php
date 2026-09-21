<?php

namespace App\Modules\Marketing\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Employee;
use App\Services\Admin\MarketingReportExportService;
use App\Services\Admin\ReportsService;
use App\Services\Marketing\Tracking\MarketingAttributionQueries;
use App\Services\Marketing\Tracking\MarketingTabReportsService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MarketingTabReportsController extends Controller
{
    use Responser;

    public function __construct(
        protected ReportsService $reports,
        protected MarketingTabReportsService $tabReports,
        protected MarketingReportExportService $export,
    ) {}

    public function overview(Request $request)
    {
        return $this->respond($request, fn (array $filter, string $channel) => $this->tabReports->overview($filter, $channel));
    }

    public function channels(Request $request)
    {
        return $this->respond($request, fn (array $filter, string $channel) => $this->tabReports->channelTable($filter, $channel));
    }

    public function export(Request $request)
    {
        try {
            $filter = $this->reports->resolveReportPeriodFilter($request);
            $channel = $this->channelFrom($request);
            $format = strtolower((string) $request->input('format', 'csv'));

            if ($format === 'email') {
                $employee = $request->user();
                if (! $employee instanceof Employee) {
                    return $this->errorMessage('يجب تسجيل الدخول كموظف لإرسال التقرير.', 403);
                }
                $this->export->emailTo($employee, $filter, $channel);

                return $this->apiResponse([], trans('api.marketing_report_emailed'));
            }

            $file = $this->export->build($filter, $format, $channel);

            return new StreamedResponse(function () use ($file) {
                echo $file['bytes'];
            }, 200, [
                'Content-Type' => $file['mime'],
                'Content-Disposition' => 'attachment; filename="'.$file['filename'].'"',
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * @param  callable(array<string, mixed>, string): array<string, mixed>  $callback
     */
    protected function respond(Request $request, callable $callback)
    {
        try {
            $filter = $this->reports->resolveReportPeriodFilter($request);
            $channel = $this->channelFrom($request);

            return $this->apiResponse(array_merge([
                'periods' => $this->reports->reportPeriodTabs($filter['key']),
                'period' => $filter['key'],
                'date_from' => $filter['date_from'],
                'date_to' => $filter['date_to'],
            ], $callback($filter, $channel)), trans('api.success'));
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    protected function channelFrom(Request $request): string
    {
        $channel = strtolower((string) $request->input('channel', 'all'));
        if ($channel === '' || $channel === 'all') {
            return 'all';
        }
        if (! in_array($channel, MarketingAttributionQueries::PAID_SOURCES, true)) {
            throw new InvalidArgumentException('قناة غير صالحة.');
        }

        return $channel;
    }
}
