<?php

namespace App\Modules\Settings\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSettingContractRequest;
use App\Http\Requests\Admin\UpdateSettingContractRequest;
use App\Http\Resources\Admin\V2\Api\SettingContractResource;
use App\Http\Traits\Responser;
use App\Models\SettingContract;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingContractController extends Controller
{
    use Responser;

    /**
     * GET /api/admin/setting-contracts
     * Alias: GET /api/admin/instrument-type-settings
     */
    public function index(Request $request)
    {
        try {
            $query = SettingContract::query();

            if ($request->filled('instrument_type')) {
                $query->where(
                    'instrument_type',
                    strtolower(trim($request->string('instrument_type')->toString()))
                );
            }

            if ($request->has('realestate')) {
                $query->where('realestate', $request->boolean('realestate'));
            }

            if ($request->has('contract')) {
                $query->where('contract', $request->boolean('contract'));
            }

            $items = $query->orderBy('id')->paginate((int) $request->get('per_page', 50));

            return $this->apiResponse([
                'items' => SettingContractResource::collection($items->items()),
                'pagination' => $this->paginate($items),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/setting-contracts
     */
    public function store(StoreSettingContractRequest $request)
    {
        try {
            $setting = SettingContract::query()->create($request->validated());

            return $this->apiResponse(
                new SettingContractResource($setting),
                trans('api.created_successfully'),
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/setting-contracts/{id}
     */
    public function show(int $id)
    {
        try {
            $setting = SettingContract::query()->findOrFail($id);

            return $this->apiResponse(
                new SettingContractResource($setting),
                trans('api.success')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * POST /api/admin/setting-contracts/{id}
     */
    public function update(UpdateSettingContractRequest $request, int $id)
    {
        try {
            $setting = SettingContract::query()->findOrFail($id);
            $setting->update($request->validated());

            return $this->apiResponse(
                new SettingContractResource($setting->fresh()),
                trans('api.updated_successfully')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }
}
