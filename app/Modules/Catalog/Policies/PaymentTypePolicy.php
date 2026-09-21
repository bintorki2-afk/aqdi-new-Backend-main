<?php

namespace App\Modules\Catalog\Policies;

class PaymentTypePolicy
{
    use ChecksEmployeeSectionPermission;

    protected function permissionSection(): string
    {
        return 'app_content';
    }
}
