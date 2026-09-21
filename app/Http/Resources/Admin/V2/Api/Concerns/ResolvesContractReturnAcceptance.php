<?php

namespace App\Http\Resources\Admin\V2\Api\Concerns;

trait ResolvesContractReturnAcceptance
{
    /**
     * @return array<string, mixed>
     */
    protected function returnAcceptanceFields(): array
    {
        $employee = $this->relationLoaded('acceptRetrunContractEmployee')
            ? $this->acceptRetrunContractEmployee
            : null;

        return [
            'accept_retrun_contract' => (bool) $this->accept_retrun_contract,
            'accept_retrun_contract_employee_id' => $this->accept_retrun_contract_employee_id,
            'return_status' => $this->resolveReturnAcceptanceStatus(),
            'accept_retrun_contract_employee' => $employee ? [
                'id' => $employee->id,
                'name' => $employee->name,
            ] : null,
        ];
    }

    /**
     * pending | accept | reject
     */
    protected function resolveReturnAcceptanceStatus(): string
    {
        if ($this->accept_retrun_contract_employee_id === null) {
            return 'pending';
        }

        return $this->accept_retrun_contract ? 'accept' : 'reject';
    }
}
