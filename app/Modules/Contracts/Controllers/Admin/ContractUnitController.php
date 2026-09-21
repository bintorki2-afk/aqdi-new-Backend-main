<?php

namespace App\Modules\Contracts\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContractUnitRequest;
use App\Http\Requests\Admin\SyncContractUnitsRequest;
use App\Http\Requests\Admin\UpdateContractUnitRequest;
use App\Http\Resources\Api\V2\UnitResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Services\ContractUnitsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ContractUnitController extends Controller
{
    use Responser;

    public function __construct(private ContractUnitsService $unitsService)
    {
    }

    public function index(int $contractId)
    {
        try {
            $contract = $this->findContract($contractId);
            $units = $contract->units()
                ->with(['unitType', 'unitUsage', 'realEstate'])
                ->get();

            return $this->apiResponse([
                'contract_id' => $contract->id,
                'uuid' => $contract->uuid,
                'real_id' => $contract->real_id,
                'items' => UnitResource::collection($units),
                'units_count' => $units->count(),
            ], trans('api.success'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.contract_not_found'), 404);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function show(int $contractId, int $unitId)
    {
        try {
            $contract = $this->findContract($contractId);
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.contract_not_found'), 404);
        }

        try {
            $unit = $contract->units()
                ->with(['unitType', 'unitUsage', 'realEstate'])
                ->where('real_units.id', $unitId)
                ->firstOrFail();

            return $this->apiResponse(
                new UnitResource($unit),
                trans('api.success')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.contract_unit_not_found'), 404);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Replace all contract units (same shape as API V2 step5).
     */
    public function sync(SyncContractUnitsRequest $request, int $contractId)
    {
        try {
            $contract = $this->findContract($contractId);
            $userId = (int) ($contract->user_id ?: 0);

            $units = $this->unitsService->syncForContract(
                $contract,
                $request->validated('units'),
                $userId,
                true
            );

            return $this->apiResponse([
                'contract_id' => $contract->id,
                'uuid' => $contract->uuid,
                'items' => UnitResource::collection(collect($units)),
                'units_count' => count($units),
            ], trans('api.contract_units_synced_successfully'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.contract_not_found'), 404);
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Attach / create one unit on the contract.
     */
    public function store(StoreContractUnitRequest $request, int $contractId)
    {
        try {
            $contract = $this->findContract($contractId);
            $userId = (int) ($contract->user_id ?: 0);

            $unit = $this->unitsService->attachToContract(
                $contract,
                $request->validated(),
                $userId,
                true
            );

            return $this->apiResponse(
                new UnitResource($unit),
                trans('api.contract_unit_added_successfully'),
                201
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.contract_not_found'), 404);
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateContractUnitRequest $request, int $contractId, int $unitId)
    {
        try {
            $contract = $this->findContract($contractId);
            $unit = $this->unitsService->updateLinkedUnit(
                $contract,
                $unitId,
                $request->validated()
            );

            return $this->apiResponse(
                new UnitResource($unit),
                trans('api.contract_unit_updated_successfully')
            );
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.contract_not_found'), 404);
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, int $contractId, int $unitId)
    {
        try {
            $contract = $this->findContract($contractId);
            $this->unitsService->detachFromContract($contract, $unitId);

            $units = $contract->units()
                ->with(['unitType', 'unitUsage', 'realEstate'])
                ->get();

            return $this->apiResponse([
                'contract_id' => $contract->id,
                'items' => UnitResource::collection($units),
                'units_count' => $units->count(),
            ], trans('api.contract_unit_removed_successfully'));
        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.contract_not_found'), 404);
        } catch (InvalidArgumentException $e) {
            return $this->errorMessage($e->getMessage(), 422);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    private function findContract(int $contractId): Contract
    {
        return Contract::findForStaffOrFail($contractId);
    }
}
