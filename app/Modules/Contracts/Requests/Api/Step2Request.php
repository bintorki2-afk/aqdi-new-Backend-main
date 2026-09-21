<?php

namespace App\Modules\Contracts\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class Step2Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:contracts,id',
            'property_place_id' => 'required|integer|exists:regions,id',
            'property_city_id' => 'required|integer|exists:cities,id',
            'neighborhood' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'building_number' => 'required|string|max:50',
            'postal_code' => 'required|string|max:20',
            'extra_figure' => 'required|string|max:255',
        ];
    }
}
