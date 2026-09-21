<?php

namespace App\Modules\Employees\Policies;

class PermissionPolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'permissions';
    }
}
