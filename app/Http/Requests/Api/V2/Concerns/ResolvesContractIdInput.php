<?php

namespace App\Http\Requests\Api\V2\Concerns;

use App\Models\Contract;

trait ResolvesContractIdInput
{
    /**
     * Accept contract_id / uuid aliases used by clients after /contract/start.
     */
    protected function resolveContractIdInput(): void
    {
        if ($this->filled('id')) {
            return;
        }

        if ($this->filled('contract_id')) {
            $this->merge(['id' => $this->input('contract_id')]);

            return;
        }

        if ($this->filled('uuid')) {
            $contractId = Contract::query()
                ->where('uuid', $this->input('uuid'))
                ->value('id');

            if ($contractId) {
                $this->merge(['id' => $contractId]);
            }
        }
    }
}
