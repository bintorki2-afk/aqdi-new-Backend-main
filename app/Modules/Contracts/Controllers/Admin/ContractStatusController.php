<?php

namespace App\Modules\Contracts\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContractStatusRequest;
use App\Http\Requests\Admin\UpdateContractStatusRequest;
use App\Http\Traits\Responser;
use App\Models\ContractStatus;
use Illuminate\Http\Request;

class ContractStatusController extends Controller
{
    use Responser;

    /**
     * Display a listing of contract statuses
     */
    public function index(Request $request)
    {
        try {
            $query = ContractStatus::query();
            $contractStatuses = $query->paginate($request->get('per_page', 20));
            return $this->apiResponse(
                [
                    'items' => $contractStatuses->items(),
                    'pagination' => $this->paginate($contractStatuses),
                ],
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

  

    /**
     * Store a newly created contract status
     */
    public function store(StoreContractStatusRequest $request)
    {
        try {
            $contractStatus = ContractStatus::create($request->validated());

            return $this->apiResponse(
                $contractStatus,
                trans('api.created_successfully'),
                201
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }
 
     
    /**
     * Update the specified contract status
     */
    public function update(UpdateContractStatusRequest $request, $id)
    {
        try {
            $contractStatus = ContractStatus::find($id);

            if (!$contractStatus) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            $contractStatus->update($request->validated());

            return $this->apiResponse(
                $contractStatus->fresh(),
                trans('api.updated_successfully')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified contract status
     */
    public function destroy($id)
    {
        try {
            $contractStatus = ContractStatus::find($id);

            if (!$contractStatus) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            $contractStatus->delete();

            return $this->apiResponse(
                [],
                trans('api.deleted_successfully')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get all active contract statuses
     */
    public function active(Request $request)
    {
        try {
            $contractStatuses = ContractStatus::where('is_active', true)
                ->orderBy('id', 'asc')
                ->get();

            return $this->apiResponse(
                $contractStatuses,
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }
}
