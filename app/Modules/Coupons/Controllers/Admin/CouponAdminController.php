<?php

namespace App\Modules\Coupons\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V2\StoreCouponRequest;
use App\Http\Requests\Admin\V2\UpdateCouponRequest;
use App\Http\Resources\Admin\V2\Api\CouponResource;
use App\Http\Traits\Responser;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class CouponAdminController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = Coupon::query()->withCount('usages');

            if (! $request->boolean('with_deleted')) {
                $query->where('is_delete', false);
            }

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code_coupon', 'like', "%{$search}%");
                });
            }

            if ($request->filled('type_coupon')) {
                $query->where('type_coupon', $request->string('type_coupon'));
            }

            if ($request->filled('status')) {
                $status = strtolower((string) $request->input('status'));
                if ($status === 'active') {
                    $query->where('is_review', true);
                } elseif (in_array($status, ['inactive', 'deactive'], true)) {
                    $query->where('is_review', false);
                }
            } elseif ($request->filled('is_review')) {
                $query->where('is_review', $request->boolean('is_review'));
            }

            $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
            $coupons = $query->latest()->paginate($perPage);

            return $this->apiResponse([
                'items' => CouponResource::collection($coupons),
                'pagination' => $this->paginate($coupons),
            ], trans('api.success'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function store(StoreCouponRequest $request)
    {
        try {
            $data = $request->validated();
            $data['is_review'] = $data['is_review'] ?? true;
            $data['is_delete'] = false;

            $coupon = Coupon::query()->create($data);
            $coupon->loadCount('usages');

            return $this->apiResponse(
                new CouponResource($coupon),
                trans('api.coupon_created_successfully'),
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $coupon = Coupon::query()->withCount('usages')->findOrFail($id);

            return $this->apiResponse(new CouponResource($coupon), trans('api.success'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateCouponRequest $request, int $id)
    {
        try {
            $coupon = Coupon::query()->findOrFail($id);
            $coupon->update($request->validated());
            $coupon->loadCount('usages');

            return $this->apiResponse(
                new CouponResource($coupon->fresh()),
                trans('api.coupon_updated_successfully')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function inactive(int $id)
    {
        try {
            $coupon = Coupon::query()->findOrFail($id);

            if ($coupon->is_delete) {
                return $this->errorMessage(trans('api.coupon_already_deleted'), 400);
            }

            $coupon->update(['is_review' => false]);
            $coupon->loadCount('usages');

            return $this->apiResponse(
                new CouponResource($coupon->fresh()),
                trans('api.coupon_inactivated_successfully')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function activate(int $id)
    {
        try {
            $coupon = Coupon::query()->findOrFail($id);

            if ($coupon->is_delete) {
                return $this->errorMessage(trans('api.coupon_already_deleted'), 400);
            }

            $coupon->update(['is_review' => true]);
            $coupon->loadCount('usages');

            return $this->apiResponse(
                new CouponResource($coupon->fresh()),
                trans('api.coupon_activated_successfully')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $coupon = Coupon::query()->findOrFail($id);
            $coupon->update(['is_delete' => true, 'is_review' => false]);

            return $this->apiResponse([], trans('api.coupon_deleted_successfully'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }
}
