<?php

namespace App\Http\Requests\Api\V2\Concerns;

use App\Support\SaudiMobile;

trait NormalizesSaudiMobileInputs
{
    /**
     * @param  list<string>  $fields
     */
    protected function normalizeSaudiMobileFields(array $fields): void
    {
        $merged = [];

        foreach ($fields as $field) {
            if (! $this->filled($field)) {
                continue;
            }

            $normalized = SaudiMobile::toNational((string) $this->input($field));
            if ($normalized !== null) {
                $merged[$field] = $normalized;
            }
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }
}
