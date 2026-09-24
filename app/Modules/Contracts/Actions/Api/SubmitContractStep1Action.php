<?php

namespace App\Modules\Contracts\Actions\Api;

use App\Models\Contract;
use Illuminate\Http\Request;

class SubmitContractStep1Action
{
    public function execute(Contract $contract, Request $request): Contract
    {
        if ($contract->is_completed) {
            abort(422, trans('api.completed_contract'));
        }

        $data = [];
        $data['contract_ownership'] = $request->contract_ownership;
        $data['instrument_number'] = $request->instrument_number;
        $data['instrument_history'] = $request->instrument_history;
        $data['real_estate_registry_number'] = $request->real_estate_registry_number;
        $data['date_first_registration'] = $request->date_first_registration;
        $data['number_of_units_in_realestate'] = $request->number_of_units_in_realestate;
        $data['instrument_type'] = $request->instrument_type ?? 'electronic';
        $data['property_type_id'] = $request->property_type_id;
        $data['property_usages_id'] = $request->property_usages_id;
        $data['number_of_floors'] = $request->number_of_floors;

        if ($request->exists('property_owner_is_deceased') && $request->property_owner_is_deceased !== null) {
            $data['property_owner_is_deceased'] = $request->boolean('property_owner_is_deceased');
        }

        if ($request->instrument_type == 'electronic') {
            $data['instrument_number'] = $request->instrument_number;
            $data['instrument_history'] = date('Y-m-d', strtotime($request->instrument_history));
        } elseif ($request->instrument_type == 'strong_argument') {
            $data['real_estate_registry_number'] = $request->real_estate_registry_number;
            $data['date_first_registration'] = $request->date_first_registration;
        }

        $data['step'] = 2;
        $contract->update($data);

        return $contract;
    }
}
