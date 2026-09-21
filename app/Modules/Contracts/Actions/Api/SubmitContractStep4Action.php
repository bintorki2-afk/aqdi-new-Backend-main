<?php

namespace App\Modules\Contracts\Actions\Api;

use App\Models\Contract;
use Illuminate\Http\Request;

class SubmitContractStep4Action
{
    public function execute(Contract $contract, Request $request): Contract
    {
        $validatedData = $request->validated();

        if ($request->hasFile('copy_of_the_owner_record')) {
            $validatedData['copy_of_the_owner_record'] = $request->file('copy_of_the_owner_record')->store('copy_of_the_owner_record', 'public');
        }

        if ($request->hasFile('copy_of_the_authorization_or_agency')) {
            $validatedData['copy_of_the_authorization_or_agency'] = $request->file('copy_of_the_authorization_or_agency')->store('authorizations', 'public');
        }

        $data = array_merge($validatedData, [
            'step' => 5,
            'copy_of_the_owner_record' => $validatedData['copy_of_the_owner_record'] ?? $contract->copy_of_the_owner_record,
            'copy_of_the_authorization_or_agency' => $validatedData['copy_of_the_authorization_or_agency'] ?? $contract->copy_of_the_authorization_or_agency,
        ]);

        $contract->update($data);

        return $contract;
    }
}
