<?php

namespace App\Modules\RealEstate\Requests\Api;

use App\Models\RealEstate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStep1Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $instrumentTypes = RealEstate::instrumentTypes();

        return [
            'id' => 'required|exists:real_estates,id',
            'contract_ownership' => 'required|in:owner,tenant',
            'instrument_number' => [Rule::requiredIf($this->instrument_type == 'electronic')],
            'instrument_history' => 'nullable|date',
            'real_estate_registry_number' => [Rule::requiredIf($this->instrument_type == 'strong_argument')],
            'date_first_registration' => [Rule::requiredIf($this->instrument_type == 'strong_argument')],
            'property_type_id' => 'required|exists:rea_estat_types,id',
            'property_owner_is_deceased' => 'required|boolean',
            'number_of_floors' => 'required',
            'instrument_type' => ['nullable', Rule::in($instrumentTypes), 'required_if:property_owner_is_deceased,1'],
            'property_usages_id' => 'required_if:instrument_type,electronic,strong_argument',
            'number_of_units_in_realestate' => 'required_if:instrument_type,electronic,strong_argument|integer',
        ];
    }
}
