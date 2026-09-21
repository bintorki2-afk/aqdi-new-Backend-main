<?php

namespace App\Modules\Contracts\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Modules\Contracts\Actions\Api\V2\LoadUncompletedContractStepsAction;
use App\Modules\Contracts\Actions\CheckUncompletedContractAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UncompeleteContractController extends Controller
{
    use Responser;

    public function checkUncompletedContract(Request $request, CheckUncompletedContractAction $action): JsonResponse
    {
        $validated = $request->validate([
            'contract_type' => ['required', Rule::in(Contract::contractTypes())],
        ]);

        $this->authorize('viewAny', Contract::class);

        return $this->apiResponse(
            $action->execute((int) auth()->id(), $validated['contract_type']),
            trans('api.success')
        );
    }

    public function getUncompletedContractStep(Request $request, LoadUncompletedContractStepsAction $action): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'string'],
        ]);

        $outcome = $action->execute($validated['uuid'], (int) auth()->id());
        if (! $outcome['ok']) {
            return $this->errorMessage($outcome['message'], $outcome['code']);
        }

        $this->authorize('view', $outcome['contract']);

        return response()->json([
            'message' => trans('api.success'),
            'code' => 200,
            'success' => true,
            'data' => $outcome['data'],
        ], 200);
    }
}
