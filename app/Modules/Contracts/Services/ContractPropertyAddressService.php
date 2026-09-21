<?php

namespace App\Modules\Contracts\Services;

use App\Models\City;
use App\Models\Contract;
use Illuminate\Http\Request;

class ContractPropertyAddressService
{
    public const PROPERTY_ADDRESS_FIELDS = [
        'property_place_id',
        'property_city_id',
        'neighborhood',
        'street',
        'building_number',
        'postal_code',
        'extra_figure',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     */
    public function mergeRequired(array &$payload, array $validated): void
    {
        foreach (self::PROPERTY_ADDRESS_FIELDS as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     * @return string|null Translation key when city does not belong to region
     */
    public function applyIfPresent(
        array &$payload,
        Request $request,
        array $validated = [],
        ?Contract $contract = null
    ): ?string {
        foreach (self::PROPERTY_ADDRESS_FIELDS as $field) {
            if ($request->filled($field)) {
                $payload[$field] = $validated[$field] ?? $request->input($field);
            }
        }

        $placeId = $payload['property_place_id'] ?? $contract?->property_place_id;
        $cityId = $payload['property_city_id'] ?? $contract?->property_city_id;

        if ($placeId && $cityId) {
            $cityBelongsToRegion = City::query()
                ->whereKey($cityId)
                ->where('region_id', $placeId)
                ->exists();

            if (! $cityBelongsToRegion) {
                return trans('api.city_not_include_region');
            }
        }

        return null;
    }

    /**
     * Name used by the V2 step actions (SubmitContractStep1Action / SubmitContractStep2Action).
     * Same behaviour as applyIfPresent(); kept as an alias so every v2 step1/step2 call no
     * longer 500s with "Call to undefined method".
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     * @return string|null Translation key when city does not belong to region
     */
    public function applyPropertyAddressIfPresent(
        array &$payload,
        Request $request,
        array $validated = [],
        ?Contract $contract = null
    ): ?string {
        return $this->applyIfPresent($payload, $request, $validated, $contract);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     */
    public function applyAddressUrlIfPresent(array &$payload, Request $request, array $validated = []): void
    {
        if ($request->filled('address_url')) {
            $payload['address_url'] = $validated['address_url'] ?? $request->input('address_url');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $validated
     */
    public function applyCoordinatesIfPresent(array &$payload, Request $request, array $validated = []): void
    {
        $latitude = $this->resolveCoordinateInput($request, $validated, 'latitude', 'lat');
        if ($latitude !== null) {
            $payload['latitude'] = $latitude;
        }

        $longitude = $this->resolveCoordinateInput($request, $validated, 'longitude', 'lng');
        if ($longitude !== null) {
            $payload['longitude'] = $longitude;
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveCoordinateInput(
        Request $request,
        array $validated,
        string $canonicalKey,
        string $aliasKey
    ): ?float {
        if ($request->filled($canonicalKey)) {
            return (float) ($validated[$canonicalKey] ?? $request->input($canonicalKey));
        }

        if ($request->filled($aliasKey)) {
            return (float) $request->input($aliasKey);
        }

        return null;
    }
}
