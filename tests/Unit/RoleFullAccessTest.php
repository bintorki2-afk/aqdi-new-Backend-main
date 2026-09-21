<?php

namespace Tests\Unit;

use App\Models\Role;
use Tests\TestCase;

class RoleFullAccessTest extends TestCase
{
    public function test_admin_slugs_and_titles_grant_full_access(): void
    {
        $this->assertTrue(Role::grantsFullAccess('admin'));
        $this->assertTrue(Role::grantsFullAccess('Admin'));
        $this->assertTrue(Role::grantsFullAccess('administrator'));
        $this->assertTrue(Role::grantsFullAccess('super-admin'));
        $this->assertTrue(Role::grantsFullAccess('أدمن'));
        $this->assertTrue(Role::grantsFullAccess('ادمن'));
        $this->assertTrue(Role::grantsFullAccess('Admin Role'));
        $this->assertTrue(Role::grantsFullAccess('branch_owner', 'Admin', 'أدمن'));
        $this->assertTrue(Role::grantsFullAccess('ops', 'System Admin', null));
        $this->assertTrue(Role::grantsFullAccess('ops', null, 'مدير النظام'));

        $this->assertFalse(Role::grantsFullAccess('operator'));
        $this->assertFalse(Role::grantsFullAccess('customer_service', 'Customer Service', 'موظف خدمة عملاء'));
    }
}
