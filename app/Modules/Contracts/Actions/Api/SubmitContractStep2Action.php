<?php

namespace App\Modules\Contracts\Actions\Api;

use App\Models\City;
use App\Models\Contract;
use Illuminate\Http\Request;

class SubmitContractStep2Action
{
    /**
     * @return array{ok: true, contract: Contract}|array{ok: false, message: string}
     */
    public function execute(Contract $contract, Request $request): array
    {
        if ($contract->is_completed) {
            return ['ok' => false, 'message' => trans('api.completed_contract')];
        }

        $city = City::where('id', $request->property_city_id)
            ->where('region_id', $request->property_place_id)
            ->first();

        if (! $city) {
            return ['ok' => false, 'message' => trans('api.city_not_include_region')];
        }

        $data = [
            'property_place_id' => $request->property_place_id,
            'property_city_id' => $request->property_city_id,
            'neighborhood' => $request->neighborhood,
            'street' => $request->street,
            'building_number' => $request->building_number,
            'postal_code' => $request->postal_code,
            'extra_figure' => $request->extra_figure,
            'step' => 3,
        ];

        $contract->update($data);

        return ['ok' => true, 'contract' => $contract];
    }
}
