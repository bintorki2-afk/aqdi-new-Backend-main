<?php

namespace App\Modules\Catalog\Policies;

class PropertyReferencePolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'property_reference';
    }
}
