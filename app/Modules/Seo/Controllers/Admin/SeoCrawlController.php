<?php

namespace App\Modules\Seo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\SeoCrawlIssueDetailResource;
use App\Http\Resources\Admin\V2\Api\SeoCrawlPageIssuesResource;
use App\Http\Traits\Responser;
use App\Jobs\RunSeoCrawlJob;
use App\Models\SeoCrawlIssue;
use App\Models\SeoCrawlRun;
use App\Services\Seo\SeoCrawlService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Throwable;

class SeoCrawlController extends Controller
{
    use Responser;

    public function __construct(protected SeoCrawlService $crawls) {}

    public function show(Request $request)
    {
        try {
            $run = $request->filled('run_id')
                ? SeoCrawlRun::query()->findOrFail((int) $request->query('run_id'))
                : $this->crawls->latestRun();

            return $this->apiResponse($this->crawls->dashboard($run), trans('api.success'));
        } catch (ModelNotFoundException $e) {
            return $this->errorMessage(trans('api.seo_crawl_not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        return $this->run($request);
    }

    public function run(Request $request)
    {
        try {
            $inProgress = $this->crawls->inProgressRun();
            if ($inProgress) {
                return $this->errorMessage(trans('api.seo_crawl_in_progress'), 409);
            }

            $validated = $request->validate([
                'url' => ['sometimes', 'nullable', 'url', 'max:512'],
                'max_pages' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2000'],
            ]);

            $maxPages = isset($validated['max_pages']) ? (int) $validated['max_pages'] : null;
            $run = $this->crawls->createRun($validated['url'] ?? null, $maxPages);

            RunSeoCrawlJob::dispatch($run->id, $maxPages)->afterResponse();

            return $this->apiResponse(
                $this->crawls->dashboard($run->fresh()),
                trans('api.seo_crawl_started'),
                202
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function stop(Request $request)
    {
        try {
            $runId = $request->filled('run_id') ? (int) $request->input('run_id') : null;

            if ($runId) {
                $existing = SeoCrawlRun::query()->find($runId);
                if (! $existing) {
                    return $this->errorMessage(trans('api.seo_crawl_not_found'), 404);
                }
            }

            $run = $this->crawls->stop($runId);
            if (! $run) {
                return $this->errorMessage(trans('api.seo_crawl_not_running'), 409);
            }

            return $this->apiResponse(
                $this->crawls->dashboard($run),
                trans('api.seo_crawl_stopped')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function issues(Request $request)
    {
        try {
            $run = $request->filled('run_id')
                ? SeoCrawlRun::query()->findOrFail((int) $request->query('run_id'))
                : $this->crawls->latestRun();

            if (! $run) {
                return $this->errorMessage(trans('api.seo_crawl_not_found'), 404);
            }

            $issues = $this->crawls->paginateIssues(
                $run,
                [
                    'type' => $request->query('type'),
                    'severity' => $request->query('severity'),
                    'search' => $request->query('search'),
                ],
                $this->perPageFromRequest($request)
            );

            return $this->paginatedApiResponse(
                $issues,
                SeoCrawlPageIssuesResource::collection($issues),
                trans('api.success')
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorMessage(trans('api.seo_crawl_not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function issue(SeoCrawlIssue $issue)
    {
        try {
            return $this->apiResponse(
                new SeoCrawlIssueDetailResource($issue->load('page.issues')),
                trans('api.success')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
