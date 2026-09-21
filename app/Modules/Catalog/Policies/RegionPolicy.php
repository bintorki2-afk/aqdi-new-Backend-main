<?php

namespace App\Modules\Catalog\Policies;

class RegionPolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'regions';
    }
}
