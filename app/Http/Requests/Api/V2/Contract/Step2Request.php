<?php

namespace App\Http\Requests\Api\V2\Contract;

use App\Http\Requests\Api\V2\BaseApiV2Request;
use App\Http\Requests\Api\V2\Concerns\ContractPropertyAddressRules;
use App\Http\Requests\Api\V2\Concerns\NormalizesCoordinateInputs;
use App\Http\Requests\Api\V2\Concerns\ResolvesContractIdInput;
use App\Models\City;
use Illuminate\Contracts\Validation\Validator;

class Step2Request extends BaseApiV2Request
{
    use ContractPropertyAddressRules;
    use NormalizesCoordinateInputs;
    use ResolvesContractIdInput;

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->resolveContractIdInput();
        $this->normalizeCoordinateInputs();
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:contracts,id',
            ...$this->contractPropertyAddressRules(require: false),
            'image_address' => 'nullable|image',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('property_city_id') || ! $this->filled('property_place_id')) {
                return;
            }

            $city = City::query()
                ->whereKey($this->input('property_city_id'))
                ->where('region_id', $this->input('property_place_id'))
                ->exists();

            if (! $city) {
                $validator->errors()->add('property_city_id', trans('api.city_not_include_region'));
            }
        });
    }

    public function messages(): array
    {
        return $this->contractV2ArabicMessages([
            'id',
            'property_place_id',
            'property_city_id',
            'neighborhood',
            'street',
            'building_number',
            'postal_code',
            'extra_figure',
            'address_url',
            'image_address',
            'latitude',
            'longitude',
            'lat',
            'lng',
        ]);
    }
}
