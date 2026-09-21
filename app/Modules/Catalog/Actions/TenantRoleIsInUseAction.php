<?php

namespace App\Modules\Catalog\Actions;

use App\Models\Contract;

class TenantRoleIsInUseAction
{
    public function execute(int $id): bool
    {
        if (Contract::query()->where('tenant_role_id', $id)->exists()) {
            return true;
        }

        return Contract::query()
            ->whereNotNull('tenant_role_ids')
            ->where('tenant_role_ids', 'like', '%"'.$id.'"%')
            ->exists();
    }
}
