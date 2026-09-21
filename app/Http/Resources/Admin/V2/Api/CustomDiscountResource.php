<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomDiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'contract_id' => $this->contract_id,
            'contract_uuid' => $this->contract_uuid,
            'type' => $this->type,
            'is_waiver' => $this->type === 'waiver',
            'value' => round((float) $this->value, 2),
            'discount_amount' => round((float) $this->discount_amount, 2),
            'total_before' => round((float) $this->total_before, 2),
            'total_after' => round((float) $this->total_after, 2),
            'reason' => $this->reason,
            'employee_id' => $this->employee_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
