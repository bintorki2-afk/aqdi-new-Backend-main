<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'title_ar' => 'مدير النظام', 'title_en' => 'System Admin', 'description' => 'صلاحيات كاملة'],
            ['name' => 'manager', 'title_ar' => 'المسؤول', 'title_en' => 'Manager', 'description' => 'إدارة الفريق والمتابعة'],
            ['name' => 'customer_service', 'title_ar' => 'موظف خدمة عملاء', 'title_en' => 'Customer Service', 'description' => 'استقبال ومتابعة العقود'],
            ['name' => 'receiver', 'title_ar' => 'موظف استلام', 'title_en' => 'Contract Receiver', 'description' => 'استلام وتسليم العقود'],
            ['name' => 'supervisor', 'title_ar' => 'مشرف', 'title_en' => 'Supervisor', 'description' => 'مراجعة العقود والمصادقة'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                array_merge($role, ['is_active' => true])
            );
        }
    }
}
