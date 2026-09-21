<?php

namespace App\Http\Resources\Admin\V2\Api\Concerns;

use App\Models\RefundableContract;
use App\Support\ContractReturnRequestFields;

trait ResolvesContractReturnOrderFields
{
    /**
     * @return array<string, mixed>
     */
    protected function returnOrderFields(): array
    {
        return ContractReturnRequestFields::for(
            $this->resource,
            $this->resolveRefundableContractRow()
        );
    }

    private function resolveRefundableContractRow(): ?RefundableContract
    {
        if ($this->relationLoaded('refundableContract')) {
            return $this->refundableContract;
        }

        return RefundableContract::query()
            ->where('contract_id', $this->resource->getKey())
            ->latest('id')
            ->first();
    }
}
