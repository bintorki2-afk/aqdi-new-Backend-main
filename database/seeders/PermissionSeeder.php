<?php

namespace Database\Seeders;

use App\Services\Admin\RolePermissionResolver;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $resolver = app(RolePermissionResolver::class);
        $created = $resolver->syncAllPermissionsFromConfig();
        $granted = $resolver->grantAllPermissionsToFullAccessRoles();

        $this->command?->info("Permissions synced. Created: {$created}; full-access grants added: {$granted}");
    }
}
