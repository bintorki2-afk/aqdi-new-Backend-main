<?php

namespace App\Modules\Contracts\Actions;

use App\Models\Contract;
use App\Modules\Contracts\Services\ContractFinancialService;

class CalculateContractFinancialAction
{
    public function __construct(
        private readonly ContractFinancialService $financial,
    ) {}

    /**
     * @return array{ok: true, contract: Contract, payload: array<string, mixed>}|array{ok: false, not_found: true}
     */
    public function execute(string $uuid, int $userId, string $version = 'v2'): array
    {
        $query = Contract::query()->ownedBy($userId);

        if ($version === 'v2') {
            $query->where(function ($inner) use ($uuid) {
                $inner->where('uuid', $uuid)->orWhere('id', $uuid);
            });
        } else {
            $query->where('uuid', $uuid);
        }

        $contract = $query->first();

        if (! $contract) {
            return ['ok' => false, 'not_found' => true];
        }

        $payload = $version === 'v2'
            ? $this->financial->forApiV2($contract)
            : $this->financial->forApiV1($contract);

        return ['ok' => true, 'contract' => $contract, 'payload' => $payload];
    }
}
