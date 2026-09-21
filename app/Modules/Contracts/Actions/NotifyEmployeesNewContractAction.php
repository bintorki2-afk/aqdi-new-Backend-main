<?php

namespace App\Modules\Contracts\Actions;

use App\Models\Contract;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

class NotifyEmployeesNewContractAction
{
    public function execute(Contract $contract, ?float $paidAmount = null): void
    {
        try {
            app(FirebaseNotificationService::class)->notifyEmployeesOfNewContract($contract, $paidAmount);
        } catch (\Throwable $e) {
            Log::warning('Failed to notify employees of new contract', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
