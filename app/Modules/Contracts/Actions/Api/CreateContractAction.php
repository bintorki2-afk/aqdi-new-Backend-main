<?php

namespace App\Modules\Contracts\Actions\Api;

use App\Models\Contract;
use Illuminate\Http\Request;

class CreateContractAction
{
    /**
     * @return array{ok: true, contract: Contract}|array{ok: false, contract: mixed}
     */
    public function execute(Request $request, int $userId): array
    {
        $data = [
            'contract_type' => $request->contract_type,
            'real_id' => $request->real_id,
            'real_units_id' => $request->real_units_id,
            'user_id' => $userId,
            'step' => 1,
        ];

        $contract = Contract::create($data);

        if ($contract && isset($contract->id)) {
            return ['ok' => true, 'contract' => $contract];
        }

        return ['ok' => false, 'contract' => $contract];
    }
}
