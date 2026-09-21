<?php

namespace App\Http\Resources\Api\V2\Contract\Concerns;

trait MapsContractAddressFields
{
    /**
     * @return array<string, mixed>
     */
    protected function contractAddressFields(): array
    {
        return [
            'property_place_id' => $this->property_place_id,
            'property_city_id' => $this->property_city_id,
            'neighborhood' => $this->neighborhood,
            'street' => $this->street,
            'building_number' => $this->building_number,
            'postal_code' => $this->postal_code,
            'extra_figure' => $this->extra_figure,
            'address_url' => $this->address_url,
        ];
    }
}
