<?php

namespace App\Modules\Employees\Policies;

class RolePolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'roles';
    }
}
