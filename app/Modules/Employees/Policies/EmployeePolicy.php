<?php

namespace App\Modules\Employees\Policies;

class EmployeePolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'employees';
    }
}
