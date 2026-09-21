<?php

namespace App\Modules\Catalog\Policies;

class TenantRolePolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'tenant_roles';
    }
}
