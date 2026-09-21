<?php

namespace App\Modules\Users\Policies;

class UserPolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'users';
    }
}
