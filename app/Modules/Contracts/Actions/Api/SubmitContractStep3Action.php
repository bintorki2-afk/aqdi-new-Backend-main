<?php

namespace App\Modules\Contracts\Actions\Api;

use App\Models\Contract;
use Illuminate\Http\Request;

class SubmitContractStep3Action
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
            'name_owner' => $request->name_owner,
            'property_owner_id_num' => $request->property_owner_id_num,
            'property_owner_dob' => $request->property_owner_dob,
            'property_owner_mobile' => $request->property_owner_mobile,
            'property_owner_iban' => $request->property_owner_iban,
            'add_legal_agent_of_owner' => $request->add_legal_agent_of_owner,
            'id_num_of_property_owner_agent' => $request->id_num_of_property_owner_agent,
            'dob_hijri_of_property_owner_agent' => $request->dob_of_property_owner_agent,
            'mobile_of_property_owner_agent' => $request->mobile_of_property_owner_agent,
            'agency_number_in_instrument_of_property_owner' => $request->agency_number_in_instrument_of_property_owner,
            'agency_instrument_date_of_property_owner' => $request->agency_instrument_date_of_property_owner,
            'step' => 4,
        ];

        $contract->update($data);

        return ['ok' => true, 'contract' => $contract];
    }
}
