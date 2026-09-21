<?php

namespace App\Modules\Catalog\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\PaymentTypeIsInUseAction;
use App\Modules\Catalog\Models\PaymentType;
use App\Modules\Catalog\Requests\Admin\StorePaymentTypeRequest;
use App\Modules\Catalog\Requests\Admin\UpdatePaymentTypeRequest;
use App\Modules\Catalog\Resources\Admin\PaymentTypeResource;
use App\Shared\Responses\Responser;
use Illuminate\Http\Request;

class PaymentTypeController extends Controller
{
    use Responser;

    public function __construct(private readonly PaymentTypeIsInUseAction $paymentTypeIsInUse)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', PaymentType::class);

        try {
            $query = PaymentType::query();

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

            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = strtolower((string) $request->get('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';
            $allowedSort = ['id', 'name_ar', 'name_en', 'contract_type', 'created_at'];
            if (! in_array($sortBy, $allowedSort, true)) {
                $sortBy = 'id';
            }

            $paymentTypes = $query
                ->orderBy($sortBy, $sortOrder)
                ->paginate($this->perPageFromRequest($request));

            return $this->paginatedApiResponse(
                $paymentTypes,
                PaymentTypeResource::collection($paymentTypes)
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(StorePaymentTypeRequest $request)
    {
        $this->authorize('create', PaymentType::class);

        $paymentType = PaymentType::query()->create($request->validated());

        return $this->apiResponse(
            new PaymentTypeResource($paymentType),
            trans('api.created_successfully'),
            201
        );
    }

    public function show(int $id)
    {
        try {
            $paymentType = PaymentType::query()->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }

        $this->authorize('view', $paymentType);

        return $this->apiResponse(
            new PaymentTypeResource($paymentType),
            trans('api.success')
        );
    }

    public function update(UpdatePaymentTypeRequest $request, int $id)
    {
        try {
            $paymentType = PaymentType::query()->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }

        $this->authorize('update', $paymentType);
        $paymentType->update($request->validated());

        return $this->apiResponse(
            new PaymentTypeResource($paymentType->fresh()),
            trans('api.updated_successfully')
        );
    }

    public function destroy(int $id)
    {
        try {
            $paymentType = PaymentType::query()->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }

        $this->authorize('delete', $paymentType);

        if ($this->paymentTypeIsInUse->execute($paymentType->id)) {
            return $this->errorMessage(trans('api.payment_type_in_use'), 422);
        }

        $paymentType->delete();

        return $this->apiResponse([], trans('api.deleted_successfully'));
    }
}
