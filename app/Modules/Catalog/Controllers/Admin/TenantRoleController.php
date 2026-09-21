<?php

namespace App\Modules\Catalog\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\BuildTenantRolePayloadAction;
use App\Modules\Catalog\Actions\TenantRoleIsInUseAction;
use App\Modules\Catalog\Models\TenantRole;
use App\Modules\Catalog\Requests\Admin\StoreTenantRoleRequest;
use App\Modules\Catalog\Resources\Admin\TenantRoleResource;
use App\Shared\Responses\Responser;
use Illuminate\Http\Request;
use Throwable;

class TenantRoleController extends Controller
{
    use Responser;

    public function __construct(
        private readonly BuildTenantRolePayloadAction $buildPayload,
        private readonly TenantRoleIsInUseAction $tenantRoleIsInUse,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', TenantRole::class);

        try {
            $query = TenantRole::query();

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('text_of_reason', 'like', '%'.$search.'%')
                        ->orWhere('service_definition', 'like', '%'.$search.'%')
                        ->orWhere('input_field_label', 'like', '%'.$search.'%');
                });
            }

            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
            $allowedSort = ['id', 'text_of_reason', 'created_at', 'updated_at'];
            if (! in_array($sortBy, $allowedSort, true)) {
                $sortBy = 'id';
            }

            $records = $query
                ->orderBy($sortBy, $sortOrder)
                ->paginate($this->perPageFromRequest($request));

            return $this->paginatedApiResponse(
                $records,
                TenantRoleResource::collection($records),
                trans('api.success')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        $record = TenantRole::query()->find($id);

        if (! $record) {
            return $this->errorMessage(trans('api.tenant_role_not_found'), 404);
        }

        $this->authorize('view', $record);

        return $this->apiResponse(
            new TenantRoleResource($record),
            trans('api.success')
        );
    }

    public function store(StoreTenantRoleRequest $request)
    {
        $this->authorize('create', TenantRole::class);

        try {
            $record = TenantRole::query()->create($this->buildPayload->execute($request));

            return $this->apiResponse(
                new TenantRoleResource($record),
                trans('api.created_successfully'),
                201
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(StoreTenantRoleRequest $request, int $id)
    {
        $record = TenantRole::query()->find($id);

        if (! $record) {
            return $this->errorMessage(trans('api.tenant_role_not_found'), 404);
        }

        $this->authorize('update', $record);

        try {
            $record->update($this->buildPayload->execute($request));

            return $this->apiResponse(
                new TenantRoleResource($record->fresh()),
                trans('api.updated_successfully')
            );
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        $record = TenantRole::query()->find($id);

        if (! $record) {
            return $this->errorMessage(trans('api.tenant_role_not_found'), 404);
        }

        $this->authorize('delete', $record);

        if ($this->tenantRoleIsInUse->execute($id)) {
            return $this->errorMessage(trans('api.tenant_role_in_use'), 422);
        }

        $record->delete();

        return $this->apiResponse([], trans('api.deleted_successfully'));
    }
}
