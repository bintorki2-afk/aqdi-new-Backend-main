<?php

namespace App\Http\Requests\Api\V2\Concerns;

trait ContractPropertyAddressRules
{
    /**
     * @return array<string, mixed>
     */
    protected function contractPropertyAddressRules(bool $require = false): array
    {
        $presence = $require ? 'required' : 'nullable';

        return [
            'property_place_id' => [$presence, 'integer', 'exists:regions,id'],
            'property_city_id' => [$presence, 'integer', 'exists:cities,id'],
            'neighborhood' => [$presence, 'string', 'max:255'],
            'street' => [$presence, 'string', 'max:255'],
            'building_number' => [$presence, 'string', 'max:50'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'extra_figure' => ['nullable', 'string', 'max:255'],
            'address_url' => ['nullable', 'string', 'max:2048'],
        ];
    }

}
