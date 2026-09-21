<?php

namespace App\Modules\Seo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchConsoleDateRangeRequest;
use App\Http\Requests\Admin\SelectSearchConsoleSiteRequest;
use App\Http\Traits\Responser;
use App\Services\Seo\SearchConsole\GoogleSearchConsolePerformanceService;
use App\Services\Seo\SearchConsole\GoogleSearchConsoleSiteService;
use App\Services\Seo\SearchConsole\GoogleSearchConsoleSitemapService;
use RuntimeException;
use Throwable;

class GoogleSearchConsoleController extends Controller
{
    use Responser;

    public function __construct(
        protected GoogleSearchConsoleSiteService $sites,
        protected GoogleSearchConsolePerformanceService $performance,
        protected GoogleSearchConsoleSitemapService $sitemaps,
    ) {}

    public function overview(SearchConsoleDateRangeRequest $request)
    {
        return $this->respond(fn () => $this->performance->overview(
            $request->fromDate(),
            $request->toDate(),
        ));
    }

    public function queries(SearchConsoleDateRangeRequest $request)
    {
        return $this->respond(fn () => $this->performance->queries(
            $request->fromDate(),
            $request->toDate(),
            $request->limit(),
            $request->startRow(),
        ));
    }

    public function pages(SearchConsoleDateRangeRequest $request)
    {
        return $this->respond(fn () => $this->performance->pages(
            $request->fromDate(),
            $request->toDate(),
            $request->limit(),
            $request->startRow(),
        ));
    }

    public function countries(SearchConsoleDateRangeRequest $request)
    {
        return $this->respond(fn () => $this->performance->countries(
            $request->fromDate(),
            $request->toDate(),
            $request->limit(),
            $request->startRow(),
        ));
    }

    public function devices(SearchConsoleDateRangeRequest $request)
    {
        return $this->respond(fn () => $this->performance->devices(
            $request->fromDate(),
            $request->toDate(),
            $request->limit(),
            $request->startRow(),
        ));
    }

    public function dates(SearchConsoleDateRangeRequest $request)
    {
        return $this->respond(fn () => $this->performance->dates(
            $request->fromDate(),
            $request->toDate(),
            $request->limit(500),
            $request->startRow(),
        ));
    }

    public function sites()
    {
        return $this->respond(fn () => $this->sites->list());
    }

    public function selectSite(SelectSearchConsoleSiteRequest $request)
    {
        try {
            $siteUrl = $this->sites->select((string) $request->validated('site_url'));

            return $this->apiResponse(
                ['site_url' => $siteUrl],
                trans('api.google_search_console_site_saved')
            );
        } catch (RuntimeException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function sitemaps()
    {
        return $this->respond(fn () => $this->sitemaps->list());
    }

    /**
     * @param  callable(): mixed  $callback
     */
    protected function respond(callable $callback)
    {
        try {
            return $this->apiResponse($callback(), trans('api.success'));
        } catch (RuntimeException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
