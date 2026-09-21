<?php

namespace App\Modules\Catalog\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\PersistPaperworkAction;
use App\Modules\Catalog\Models\Paperwork;
use App\Modules\Catalog\Requests\Admin\StorePaperworkRequest;
use App\Modules\Catalog\Requests\Admin\UpdatePaperworkRequest;
use App\Modules\Catalog\Resources\Admin\PaperworkResource;
use App\Shared\Responses\Responser;
use Illuminate\Http\Request;

class PaperworkController extends Controller
{
    use Responser;

    public function __construct(private readonly PersistPaperworkAction $persistPaperwork)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Paperwork::class);

        try {
            $query = Paperwork::query();

            if ($request->filled('contract_type')) {
                $query->where('contract_type', $request->string('contract_type'));
            }

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            }

            $paperworks = $query->latest()->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse([
                'items' => PaperworkResource::collection($paperworks->items()),
                'pagination' => $this->paginate($paperworks),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(StorePaperworkRequest $request)
    {
        $this->authorize('create', Paperwork::class);

        try {
            $paperwork = $this->persistPaperwork->execute($request);

            return $this->apiResponse(
                new PaperworkResource($paperwork),
                trans('api.created_successfully'),
                201
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $paperwork = Paperwork::query()->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }

        $this->authorize('view', $paperwork);

        return $this->apiResponse(
            new PaperworkResource($paperwork),
            trans('api.success')
        );
    }

    public function update(UpdatePaperworkRequest $request, int $id)
    {
        try {
            $paperwork = Paperwork::query()->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }

        $this->authorize('update', $paperwork);

        try {
            $paperwork = $this->persistPaperwork->execute($request, $paperwork);

            return $this->apiResponse(
                new PaperworkResource($paperwork),
                trans('api.updated_successfully')
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $paperwork = Paperwork::query()->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }

        $this->authorize('delete', $paperwork);
        $this->persistPaperwork->deleteIconFile($paperwork);
        $paperwork->delete();

        return $this->apiResponse([], trans('api.deleted_successfully'));
    }
}
