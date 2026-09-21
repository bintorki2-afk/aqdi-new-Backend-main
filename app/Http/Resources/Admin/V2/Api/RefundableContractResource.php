<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundableContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'employee_id' => $this->employee_id,
            'user_id' => $this->user_id,
            'has_draft_contract' => (bool) $this->has_draft_contract,
            'refund_amount' => $this->refund_amount,
            'notes' => $this->notes,
            'admin_confirmed' => (bool) $this->admin_confirmed,
            'is_refunded' => (bool) $this->is_refunded,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'contract' => $this->whenLoaded('contract', fn () => [
                'id' => $this->contract?->id,
                'uuid' => $this->contract?->uuid,
                'contract_type' => $this->contract?->contract_type_trans,
                'contract_type_key' => $this->contract?->contract_type,
                'instrument_type' => $this->contract?->instrument_type_trans,
                'instrument_type_key' => $this->contract?->instrument_type,
                'is_completed' => (bool) $this->contract?->is_completed,
                'created_at' => $this->contract?->created_at?->format('Y-m-d H:i:s'),
            ]),
        ];
    }
}
