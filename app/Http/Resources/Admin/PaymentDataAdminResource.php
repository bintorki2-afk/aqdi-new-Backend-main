<?php

namespace App\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentDataAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $contract = $this->contract;
        $user = optional($contract)->user;

        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'payment_date' => $this->payment_date ? Carbon::parse($this->payment_date)->format('Y-m-d') : null,
            'payment_hour' => $this->payment_date ? Carbon::parse($this->payment_date)->format('H:i') : null,
            'contract_uuid' => $this->contract_uuid,
            'contract_type' => $contract?->contract_type,
            'payment_method' => $this->payment_method,
            'tran_currency' => $this->tran_currency,
            'name_payment' => $this->name,
            'name' => $this->name,
            'status' => $this->status,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'email' => $user->email,
            ] : null,
            'user_mobile' => $user?->mobile,
        ];
    }
}
