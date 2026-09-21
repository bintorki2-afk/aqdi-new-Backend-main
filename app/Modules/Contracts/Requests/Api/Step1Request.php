<?php

namespace App\Modules\Contracts\Requests\Api;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Step1Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $instrumentType = $this->input('instrument_type');
        $skipInitialStepRequirements = Contract::shouldSkipInitialSteps(
            $instrumentType !== null ? (string) $instrumentType : null
        );

        return [
            'id' => 'required|exists:contracts,id',
            'contract_ownership' => [
                Rule::requiredIf(! $skipInitialStepRequirements),
                'in:owner,tenant',
            ],
            'instrument_type' => 'nullable',
            'instrument_number' => 'required_if:instrument_type,electronic',
            'instrument_history' => 'required_if:instrument_type,electronic',
            'real_estate_registry_number' => 'required_if:instrument_type,strong_argument',
            'date_first_registration' => 'required_if:instrument_type,strong_argument',
            'property_type_id' => [
                Rule::requiredIf(! $skipInitialStepRequirements),
                'exists:rea_estat_types,id',
            ],
            'property_owner_is_deceased' => [
                Rule::requiredIf(! $skipInitialStepRequirements),
                'boolean',
            ],
            'number_of_floors' => [
                Rule::requiredIf(! $skipInitialStepRequirements),
            ],
            'property_usages_id' => 'required_if:instrument_type,electronic,strong_argument',
            'number_of_units_in_realestate' => 'required_if:instrument_type,electronic,strong_argument|integer',
        ];
    }
}
