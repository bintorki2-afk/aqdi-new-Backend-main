<?php

namespace App\Modules\Catalog\Policies;

class PaperworkPolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'paperworks';
    }
}
