<?php

namespace App\Http\Requests\Api\V2\Concerns;

trait NormalizesCoordinateInputs
{
    /**
     * Map mobile / map widget aliases (lat, lng) to DB/API fields (latitude, longitude).
     */
    protected function normalizeCoordinateInputs(): void
    {
        $merge = [];

        if (! $this->filled('latitude') && $this->filled('lat')) {
            $merge['latitude'] = $this->input('lat');
        }

        if (! $this->filled('longitude') && $this->filled('lng')) {
            $merge['longitude'] = $this->input('lng');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
