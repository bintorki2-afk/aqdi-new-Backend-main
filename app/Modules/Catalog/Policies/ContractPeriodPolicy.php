<?php

namespace App\Modules\Catalog\Policies;

class ContractPeriodPolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'contract_periods';
    }
}
