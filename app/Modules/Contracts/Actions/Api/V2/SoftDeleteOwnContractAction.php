<?php

namespace App\Modules\Contracts\Actions\Api\V2;

use App\Models\Contract;

class SoftDeleteOwnContractAction
{
    /**
     * @return array{ok: true}|array{ok: false, message: string, code: int}
     */
    public function execute(?Contract $contract): array
    {
        if (! $contract) {
            return ['ok' => false, 'message' => trans('api.contract_not_found'), 'code' => 404];
        }

        if ($contract->is_completed) {
            return ['ok' => false, 'message' => trans('api.completed_contract'), 'code' => 400];
        }

        $contract->update(['is_delete' => true]);

        return ['ok' => true];
    }
}
