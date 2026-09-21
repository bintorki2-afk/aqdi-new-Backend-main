<?php

namespace App\Http\Resources\Admin\V2\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractPaidByEmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_uuid' => $this->contract_uuid !== null ? (string) $this->contract_uuid : null,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->name),
            'customer_mobile' => $this->customer_mobile,
            'contract_type' => $this->contract_type,
            'contract_type_label' => $this->contract_type === 'commercial'
                ? 'تجاري'
                : ($this->contract_type === 'housing' ? 'سكني' : null),
            'contract_period_id' => $this->contract_period_id,
            'contract_period' => $this->whenLoaded('contractPeriod', function () {
                $period = $this->contractPeriod;
                if (! $period) {
                    return null;
                }

                return [
                    'id' => $period->id,
                    'period' => $period->period,
                    'price' => $period->price !== null ? (float) $period->price : null,
                    'contract_type' => $period->contract_type,
                    'note' => $period->note_trans ?? $period->note_ar,
                ];
            }),
            'draft_contract_number' => $this->draft_contract_number,
            'draft_contract_id' => $this->draft_contract_id,
            'amount' => (float) $this->amount,
            'is_paid' => (bool) $this->is_paid,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
