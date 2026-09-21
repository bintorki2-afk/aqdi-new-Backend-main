<?php

namespace App\Modules\Contracts\Actions;

use App\Http\Resources\Api\V2\Contract\Step1Resource;
use App\Http\Resources\Api\V2\Contract\Step2Resource;
use App\Http\Resources\Api\V2\Contract\Step3Resource;
use App\Http\Resources\Api\V2\Contract\Step4Resource;
use App\Http\Resources\Api\V2\Contract\Step5Resource;
use App\Http\Resources\Api\V2\Contract\Step6Resource;
use App\Models\Contract;

class LoadUncompletedContractStepsAction
{
    /**
     * @return array{ok: bool, contract?: Contract, data?: array<string, mixed>, message?: string, code?: int}
     */
    public function execute(int $userId, string $uuid): array
    {
        $contract = Contract::query()
            ->where('user_id', $userId)
            ->where('uuid', $uuid)
            ->where('is_delete', false)
            ->with([
                'realEstate',
                'contractTermInYears',
                'contractStatus',
                'draftContractStatus',
                'units.unitType',
                'units.unitUsage',
                'units.realEstate',
            ])
            ->first();

        if (! $contract) {
            return ['ok' => false, 'message' => trans('api.contract_not_found'), 'code' => 404];
        }

        if ($contract->is_completed) {
            return ['ok' => false, 'message' => trans('api.completed_contract'), 'code' => 400];
        }

        return [
            'ok' => true,
            'contract' => $contract,
            'data' => array_merge([
                'step' => (int) $contract->step,
                'contract_id' => $contract->id,
                'uuid' => (string) $contract->uuid,
            ], $this->buildPreviousStepsData($contract)),
        ];
    }

    /**
     * @return list<int>
     */
    private function applicableStepNumbers(Contract $contract): array
    {
        if (! Contract::shouldSkipInitialSteps($contract->instrument_type)) {
            return [1, 2, 3, 4, 5, 6];
        }

        if ($contract->instrument_type === 'lease_renewal') {
            return [3, 5, 6];
        }

        return [3, 4, 5, 6];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPreviousStepsData(Contract $contract): array
    {
        $currentStep = (int) $contract->step;
        $data = [];

        foreach ($this->applicableStepNumbers($contract) as $stepNumber) {
            if ($stepNumber >= $currentStep) {
                break;
            }

            $data['step'.$stepNumber] = $this->resolveStepPayload($stepNumber, $contract);
        }

        return $data;
    }

    private function resolveStepPayload(int $stepNumber, Contract $contract): mixed
    {
        return match ($stepNumber) {
            1 => new Step1Resource($contract),
            2 => new Step2Resource($contract),
            3 => new Step3Resource($contract),
            4 => new Step4Resource($contract),
            5 => new Step5Resource($contract),
            6 => new Step6Resource($contract->loadMissing('contractTermInYears')),
            default => [],
        };
    }
}
