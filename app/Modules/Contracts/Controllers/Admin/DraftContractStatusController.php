<?php

namespace App\Modules\Contracts\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDraftContractStatusRequest;
use App\Http\Requests\Admin\UpdateDraftContractStatusRequest;
use App\Http\Traits\Responser;
use App\Models\ContractStatus;
use App\Models\DraftContractStatus;
use Illuminate\Http\Request;

class DraftContractStatusController extends Controller
{
    use Responser;

    /**
     * GET /api/admin/draft-contract-statuses
     */
    public function index(Request $request)
    {
        try {
            $query = DraftContractStatus::query()->orderBy('id');
            $statuses = $query->paginate($request->get('per_page', 20));

            return $this->apiResponse(
                [
                    'items' => $statuses->items(),
                    'pagination' => $this->paginate($statuses),
                ],
                trans('api.success')
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/draft-contract-statuses/active
     */
    public function active()
    {
        try {
            $statuses = DraftContractStatus::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            return $this->apiResponse($statuses, trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/draft-contract-statuses
     */
    public function store(StoreDraftContractStatusRequest $request)
    {
        try {
            $status = DraftContractStatus::query()->create($request->validated());

            return $this->apiResponse($status, trans('api.created_successfully'), 201);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/draft-contract-statuses/{id}
     */
    public function show($id)
    {
        $status = DraftContractStatus::query()->find($id);

        if (! $status) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }

        return $this->apiResponse($status, trans('api.success'));
    }

    /**
     * POST /api/admin/draft-contract-statuses/{id}
     */
    public function update(UpdateDraftContractStatusRequest $request, $id)
    {
        try {
            $status = DraftContractStatus::query()->find($id);

            if (! $status) {
                return $this->errorMessage(trans('api.not_found'), 404);
            }

            $status->update($request->validated());

            return $this->apiResponse($status->fresh(), trans('api.updated_successfully'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/draft-contract-statuses/{id}/delete
     */
    public function destroy($id)
    {
        try {
            $status = DraftContractStatus::query()->find($id);

            if (! $status) {
                return $this->errorMessage(trans('api.not_found'), 404);
            }

            $status->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * Copy missing statuses from contract_statuses into draft_contract_statuses.
     * POST /api/admin/draft-contract-statuses/sync
     */
    public function syncFromContractStatuses()
    {
        try {
            $existingNames = DraftContractStatus::query()->pluck('name')->all();
            $created = [];

            foreach (ContractStatus::query()->orderBy('id')->get() as $status) {
                if (in_array($status->name, $existingNames, true)) {
                    continue;
                }

                $payload = [
                    'name' => $status->name,
                    'color' => $status->color,
                    'color_text' => $status->color_text,
                    'description' => $status->description,
                    'client_explanation' => $status->client_explanation,
                    'is_active' => (bool) $status->is_active,
                ];

                $created[] = DraftContractStatus::query()->create($payload);
            }

            return $this->apiResponse(
                [
                    'created_count' => count($created),
                    'created' => $created,
                    'items' => DraftContractStatus::query()->orderBy('id')->get(),
                ],
                trans('api.success')
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
