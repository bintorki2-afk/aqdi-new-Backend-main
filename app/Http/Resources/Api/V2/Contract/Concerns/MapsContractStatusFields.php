<?php

namespace App\Http\Resources\Api\V2\Contract\Concerns;

use App\Models\Contract;
use App\Support\ContractFrontendStatus;

trait MapsContractStatusFields
{
    /**
     * @return array<string, mixed>
     */
    protected function contractStatusFields(?string $defaultName = null): array
    {
        return self::contractStatusFieldsFor($this->resource, $defaultName);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function contractStatusFieldsFor(?object $contract, ?string $defaultName = null): array
    {
        if ($contract === null) {
            return [
                'contract_status_id' => null,
                'contract_status_name' => $defaultName,
                'status' => 'unknown',
                'status_label' => $defaultName ?? 'غير محدد',
                'status_type' => 'contract',
                'status_id' => null,
                'status_color' => null,
                'status_description' => null,
                'status_client_explanation' => null,
            ];
        }

        $name = optional($contract->contractStatus)->name;
        $frontend = $contract instanceof Contract
            ? ContractFrontendStatus::for($contract)
            : [
                'status' => ContractFrontendStatus::keyFromName(
                    $name,
                    isset($contract->contract_status_id) ? 'contract_'.$contract->contract_status_id : 'under_review'
                ),
                'status_label' => $name ? (string) (trans($name) ?: $name) : ($defaultName ?? 'قيد المراجعة'),
                'status_type' => 'contract',
                'status_id' => isset($contract->contract_status_id) ? (int) $contract->contract_status_id : null,
                'status_color' => optional($contract->contractStatus)->color,
                'status_description' => optional($contract->contractStatus)->client_explanation
                    ?: optional($contract->contractStatus)->description,
                'status_client_explanation' => optional($contract->contractStatus)->client_explanation,
            ];

        if ($defaultName && ($frontend['status_label'] === null || $frontend['status_label'] === '')) {
            $frontend['status_label'] = $defaultName;
        }

        return [
            'contract_status_id' => $contract->contract_status_id ?? null,
            'contract_status_name' => $name ? trans($name) : $defaultName,
            'status' => $frontend['status'],
            'status_label' => $frontend['status_label'],
            'status_type' => $frontend['status_type'],
            'status_id' => $frontend['status_id'],
            'status_color' => $frontend['status_color'],
            'status_description' => $frontend['status_description'],
            'status_client_explanation' => $frontend['status_client_explanation'] ?? null,
        ];
    }
}
