<?php

namespace App\Modules\Catalog\Policies;

class CityPolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'cities';
    }
}
