<?php

namespace App\Http\Resources\Admin\V2\Api;

use App\Enums\ReceivedContractStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceivedContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $workflowStatus = $this->status instanceof ReceivedContractStatus
            ? $this->status
            : ReceivedContractStatus::tryFrom((string) $this->status) ?? ReceivedContractStatus::Pending;

        // A row in received_contracts means the contract was received (do not map workflow pending → "لم يُستلم بعد").
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'employee_id' => $this->employee_id,
            'status' => $workflowStatus->value,
            'date_of_received' => $this->date_of_received?->format('Y-m-d'),
            'notes' => $this->notes,
            'employeeId' => $this->employee?->id,
            'employeeName' => $this->employee?->name,
            'employeeEmail' => $this->employee?->email,
            'employeePhone' => $this->employee?->phone,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'receipt_status' => 'received',
            'receipt_status_label_ar' => 'مستلم',
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
