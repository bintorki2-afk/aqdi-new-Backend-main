<?php

namespace App\Modules\Contracts\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class Step3Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name_owner' => 'required|string',
            'property_owner_id_num' => 'required|min:10',
            'property_owner_dob' => 'required',
            'property_owner_mobile' => 'required|min:10|regex:/^05[0-9]{8}$/',
            'property_owner_iban' => 'nullable|min:22',
            'add_legal_agent_of_owner' => 'required',
            'id_num_of_property_owner_agent' => 'nullable|required_if:add_legal_agent_of_owner,1|min:10',
            'dob_of_property_owner_agent' => 'nullable|required_if:add_legal_agent_of_owner,1',
            'mobile_of_property_owner_agent' => 'nullable|required_if:add_legal_agent_of_owner,1|min:10|regex:/^05[0-9]{8}$/',
            'agency_number_in_instrument_of_property_owner' => 'nullable|required_if:add_legal_agent_of_owner,1',
            'agency_instrument_date_of_property_owner' => 'nullable|required_if:add_legal_agent_of_owner,1',
        ];
    }
}
