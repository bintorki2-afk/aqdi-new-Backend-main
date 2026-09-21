<?php

namespace App\Http\Requests\Api\V2\RealEstate\Concerns;

use App\Models\Contract;

trait NormalizesRealEstateInstrumentType
{
    /**
     * Map mobile aliases (e.g. electronic_deed_from_the_ministry_of_justice) to stored enum values.
     */
    protected function normalizeInstrumentTypeInput(): void
    {
        if (! $this->has('instrument_type')) {
            return;
        }

        $normalized = Contract::normalizeInstrumentType($this->input('instrument_type'));

        if ($normalized !== null) {
            $this->merge(['instrument_type' => $normalized]);
        }
    }

    /**
     * Manual deed entry (number / date) instead of uploading a scan.
     * In that path the deed image is optional.
     */
    protected function isManualDeedEntry(): bool
    {
        return $this->filled('instrument_number')
            || $this->filled('instrument_history')
            || (
                $this->filled('instrument_history_day')
                && $this->filled('instrument_history_month')
                && $this->filled('instrument_history_year')
            );
    }

    protected function requiresElectronicDeedImage(): bool
    {
        return $this->input('instrument_type') === 'electronic'
            && ! $this->isManualDeedEntry();
    }
}
