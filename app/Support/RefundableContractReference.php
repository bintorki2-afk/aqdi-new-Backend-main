<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\RefundableContract;

class RefundableContractReference
{
    public static function for(RefundableContract $record): string
    {
        $contract = $record->relationLoaded('contract')
            ? $record->contract
            : Contract::query()->find($record->contract_id);

        if ($contract && filled($contract->uuid)) {
            $paymentRef = Payment::query()
                ->where('contract_uuid', $contract->uuid)
                ->where('status', 'success')
                ->orderByDesc('created_at')
                ->value('name');

            if ($paymentRef && preg_match('/^[A-Za-z0-9_\-]+$/', (string) $paymentRef) && ! str_contains((string) $paymentRef, ' ')) {
                return (string) $paymentRef;
            }
        }

        return 'REF-'.str_pad((string) $record->id, 6, '0', STR_PAD_LEFT);
    }
}
