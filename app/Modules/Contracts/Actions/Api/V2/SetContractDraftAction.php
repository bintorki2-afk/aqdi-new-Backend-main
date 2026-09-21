<?php

namespace App\Modules\Contracts\Actions\Api\V2;

use App\Http\Requests\Api\V2\Contract\SetContractDraftRequest;
use App\Models\Contract;
use App\Models\DraftContractStatus;
use App\Modules\Contracts\Actions\NotifyEmployeesNewContractAction;

class SetContractDraftAction
{
    public function __construct(
        private readonly NotifyEmployeesNewContractAction $notifyEmployees,
    ) {}

    /**
     * @return array{ok: true, contract: Contract}|array{ok: false, message: string, code?: int}
     */
    public function execute(Contract $contract, SetContractDraftRequest $request): array
    {
        if ($contract->is_completed) {
            return ['ok' => false, 'message' => trans('api.completed_contract')];
        }

        $wasDraft = (bool) $contract->is_draft;
        $isDraft = $request->boolean('is_draft');

        $updates = [
            'is_draft' => $isDraft,
        ];

        if ($isDraft && ! $contract->draft_contract_status_id) {
            $newDraftStatusId = DraftContractStatus::newStatusId();
            if ($newDraftStatusId !== null) {
                $updates['draft_contract_status_id'] = $newDraftStatusId;
            }
        }

        $contract->update($updates);

        if ($isDraft && ! $wasDraft) {
            $this->notifyEmployees->execute($contract->fresh(['user']));
        }

        return ['ok' => true, 'contract' => $contract->fresh(['realEstate', 'contractStatus'])];
    }
}
