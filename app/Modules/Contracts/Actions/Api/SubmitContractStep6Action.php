<?php

namespace App\Modules\Contracts\Actions\Api;

use App\Models\Contract;
use App\Support\ContractStartingDateInput;
use Illuminate\Http\Request;

class SubmitContractStep6Action
{
    /**
     * @return array{ok: true, contract: Contract}|array{ok: false, message: string}
     */
    public function execute(Contract $contract, Request $request): array
    {
        if ($contract->is_completed) {
            return ['ok' => false, 'message' => trans('api.completed_contract')];
        }

        $data = [
            'contract_starting_date' => ContractStartingDateInput::resolveForStorage($request),
            'contract_term_in_years' => $request->contract_term_in_years,
            'annual_rent_amount_for_the_unit' => $request->annual_rent_amount_for_the_unit,
            'payment_type_id' => $request->payment_type_id,
            'step' => 7,
        ];

        if ($request->filled('other_conditions')) {
            $data['other_conditions'] = $request->other_conditions;
        }

        if ($request->filled('daily_fine')) {
            $data['daily_fine'] = $request->daily_fine;
        }

        $contract->update($data);

        return ['ok' => true, 'contract' => $contract];
    }
}
