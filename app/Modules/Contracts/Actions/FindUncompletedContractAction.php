<?php

namespace App\Modules\Contracts\Actions;

use App\Models\Contract;

class FindUncompletedContractAction
{
    public function execute(int $userId, string $contractType): ?Contract
    {
        return Contract::query()
            ->incompleteForUser($userId, $contractType)
            ->latest('created_at')
            ->first();
    }

    /**
     * @return array{check: bool, contract_id?: int, uuid?: string, step?: mixed, contract_type?: string}
     */
    public function payload(int $userId, string $contractType): array
    {
        $contract = $this->execute($userId, $contractType);

        if (! $contract) {
            return ['check' => false];
        }

        return [
            'check' => true,
            'contract_id' => $contract->id,
            'uuid' => (string) $contract->uuid,
            'step' => $contract->step,
            'contract_type' => $contract->contract_type,
        ];
    }
}
