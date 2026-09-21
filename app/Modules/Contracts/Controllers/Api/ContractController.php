<?php

namespace App\Modules\Contracts\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Http\Resources\SearchResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Modules\Contracts\Actions\Api\CreateContractAction;
use App\Modules\Contracts\Actions\Api\GetContractFilesAction;
use App\Modules\Contracts\Actions\Api\SearchContractsAction;
use App\Modules\Contracts\Actions\Api\SubmitContractStep1Action;
use App\Modules\Contracts\Actions\Api\SubmitContractStep2Action;
use App\Modules\Contracts\Actions\Api\SubmitContractStep3Action;
use App\Modules\Contracts\Actions\Api\SubmitContractStep4Action;
use App\Modules\Contracts\Actions\Api\SubmitContractStep5Action;
use App\Modules\Contracts\Actions\Api\SubmitContractStep6Action;
use App\Modules\Contracts\Actions\CalculateContractFinancialAction;
use App\Modules\Contracts\Actions\CheckUncompletedContractAction;
use App\Modules\Contracts\Actions\ListUserContractsAction;
use App\Modules\Contracts\Requests\Api\CheckUncompletedContractRequest;
use App\Modules\Contracts\Requests\Api\CreateContractRequest;
use App\Modules\Contracts\Requests\Api\Step1Request;
use App\Modules\Contracts\Requests\Api\Step2Request;
use App\Modules\Contracts\Requests\Api\Step3Request;
use App\Modules\Contracts\Requests\Api\Step4Request;
use App\Modules\Contracts\Requests\Api\Step5Request;
use App\Modules\Contracts\Requests\Api\Step6Request;

class ContractController extends Controller
{
    use Responser;

    public function index(ListUserContractsAction $action)
    {
        $this->authorize('viewAny', Contract::class);

        $contracts = $action->paginateForApi((int) auth()->id());

        return $this->apiResponse([
            'data' => ContractResource::collection($contracts),
            'pagination' => $this->paginate($contracts),
        ], trans('api.success'));
    }

    public function show($id)
    {
        $contract = Contract::findOwnedOrFail($id);
        $this->authorize('view', $contract);

        return $this->apiResponse(new ContractResource($contract), trans('api.success'));
    }

    public function checkUncompletedContract(CheckUncompletedContractRequest $request, CheckUncompletedContractAction $action)
    {
        $this->authorize('viewAny', Contract::class);

        return $this->apiResponse(
            $action->execute((int) auth()->id(), $request->validated('contract_type')),
            trans('api.success')
        );
    }

    public function contractType(CreateContractRequest $request, CreateContractAction $action)
    {
        $this->authorize('create', Contract::class);

        $outcome = $action->execute($request, (int) auth()->id());

        if ($outcome['ok']) {
            return response()->json([
                'message' => trans('api.success'),
                'code' => 200,
                'success' => true,
                'data' => [
                    'contract_id' => $outcome['contract']->id,
                    'uuid' => (string) $outcome['contract']->uuid,
                ],
            ]);
        }

        return response()->json([
            'message' => trans('api.failure'),
            'code' => 500,
            'success' => false,
            'data' => [
                'id' => $outcome['contract'],
            ],
        ]);
    }

    public function step1(Step1Request $request, SubmitContractStep1Action $action)
    {
        $contract = Contract::findOwnedOrFail($request->id);
        $this->authorize('update', $contract);

        $contract = $action->execute($contract, $request);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => [
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
            ],
        ]);
    }

    public function step2(Step2Request $request, SubmitContractStep2Action $action)
    {
        $contract = Contract::findOwnedOrFail($request->id);
        $this->authorize('update', $contract);

        $outcome = $action->execute($contract, $request);
        if (! $outcome['ok']) {
            return $this->errorMessage($outcome['message']);
        }

        $contract = $outcome['contract'];

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => [
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
                'instrument_type' => $contract->instrument_type,
                'image_instrument' => $contract->image_instrument ? getFilePath($contract->image_instrument) : null,
            ],
        ]);
    }

    public function step3(Step3Request $request, SubmitContractStep3Action $action)
    {
        $contract = Contract::findOwnedOrFail($request->id);
        $this->authorize('update', $contract);

        $outcome = $action->execute($contract, $request);
        if (! $outcome['ok']) {
            return $this->errorMessage($outcome['message']);
        }

        $contract = $outcome['contract'];

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => [
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
            ],
        ]);
    }

    public function step4(Step4Request $request, SubmitContractStep4Action $action)
    {
        $contract = Contract::findOwnedOrFail($request->id);
        $this->authorize('update', $contract);

        $contract = $action->execute($contract, $request);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => [
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
            ],
        ]);
    }

    public function step5(Step5Request $request, SubmitContractStep5Action $action)
    {
        $contract = Contract::findOwnedOrFail($request->id);
        $this->authorize('update', $contract);

        $contract = $action->execute($contract, $request);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => [
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
            ],
        ]);
    }

    public function step6(Step6Request $request, SubmitContractStep6Action $action)
    {
        $contract = Contract::findOwnedOrFail($request->id);
        $this->authorize('update', $contract);

        $outcome = $action->execute($contract, $request);
        if (! $outcome['ok']) {
            return $this->errorMessage($outcome['message']);
        }

        $contract = $outcome['contract'];

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => [
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
                'price_contract_term' => $contract->contractTermInYears->price ?? null,
            ],
        ]);
    }

    public function getContracts($uuid, GetContractFilesAction $action)
    {
        $this->authorize('viewAny', Contract::class);

        $files = $action->execute($uuid, (int) auth()->id());

        if ($files !== []) {
            return $this->apiResponse(['files' => $files], trans('api.success'));
        }

        return $this->apiResponse(null, trans('api.waitContract'));
    }

    public function search($searchTerm, SearchContractsAction $action)
    {
        $this->authorize('viewAny', Contract::class);

        $contracts = $action->execute($searchTerm, (int) auth()->id());

        if ($contracts->isEmpty()) {
            return $this->apiResponse(null, trans('api.error'));
        }

        return $this->apiResponse(SearchResource::collection($contracts), trans('api.success'));
    }

    public function financial($uuid, CalculateContractFinancialAction $action)
    {
        $outcome = $action->execute((string) $uuid, (int) auth()->id(), 'v1');

        if (! $outcome['ok']) {
            return response()->json([
                'message' => 'العقد غير موجود',
                'success' => false,
                'data' => [],
            ], 404);
        }

        $this->authorize('view', $outcome['contract']);

        return response()->json([
            'status' => 'success',
            'message' => 'التفاصيل الماليه',
            'data' => $outcome['payload'],
        ], 200);
    }
}
