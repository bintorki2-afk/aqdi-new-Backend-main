<?php

namespace App\Modules\Marketing\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMarketingServicePageRequest;
use App\Http\Requests\Admin\UpdateMarketingServicePageRequest;
use App\Http\Traits\Responser;
use App\Models\MarketingServicePage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Throwable;

class MarketingServicePageController extends Controller
{
    use Responser;

    public function index()
    {
        try {
            $pages = MarketingServicePage::query()
                ->orderByDesc('updated_at')
                ->get();

            return $this->apiResponse([
                'summary' => [
                    'total' => $pages->count(),
                    'published' => $pages->where('status', 'published')->count(),
                    'drafts' => $pages->where('status', 'draft')->count(),
                ],
                'items' => $pages->map(fn (MarketingServicePage $page) => $page->toMarketingRow())->values()->all(),
            ], trans('api.success'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(StoreMarketingServicePageRequest $request)
    {
        try {
            $page = MarketingServicePage::query()->create($request->validated());

            return $this->apiResponse($page->toMarketingRow(), trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorMessage($this->firstValidationMessage($e), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(UpdateMarketingServicePageRequest $request, int $id)
    {
        try {
            $page = MarketingServicePage::query()->findOrFail($id);
            $page->update($request->validated());

            return $this->apiResponse($page->fresh()->toMarketingRow(), trans('api.updated_successfully'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorMessage($this->firstValidationMessage($e), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $page = MarketingServicePage::query()->findOrFail($id);
            $page->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    protected function firstValidationMessage(ValidationException $e): string
    {
        return collect($e->errors())->flatten()->first() ?: 'البيانات غير صالحة.';
    }
}
