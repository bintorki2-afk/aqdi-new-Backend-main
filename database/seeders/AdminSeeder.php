<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'name' => 'أدمن النظام',
                'email' => 'admin@aqdi.com',
                'password' => bcrypt('Admin@123'),
                'mobile' => '966501234500',
                'is_admin' => true,
            ],
            [
                'name' => 'أحمد المشرف',
                'email' => 'supervisor@aqdi.com',
                'password' => bcrypt('Supervisor@123'),
                'mobile' => '966501234501',
                'is_admin' => false,
            ],
            [
                'name' => 'سارة الموظفة',
                'email' => 'sara@aqdi.com',
                'password' => bcrypt('Sara@123'),
                'mobile' => '966501234502',
                'is_admin' => false,
            ],
        ];

        foreach ($admins as $admin) {
            Admin::updateOrCreate(
                ['email' => $admin['email']],
                $admin
            );
        }

        // The dashboard (`POST /api/admin/login`) authenticates against the `employees`
        // table, NOT `admins`. Without this row a fresh production install has no way to
        // sign in to the dashboard (QA finding). Password is the same as above — change it
        // after first login. Idempotent: never resets the password of an existing employee.
        $adminRole = Role::query()->where('name', 'admin')->first();

        Employee::query()->firstOrCreate(
            ['email' => 'admin@aqdi.com'],
            [
                'name' => 'أدمن النظام',
                'password' => bcrypt('Admin@123'),
                'phone' => '966501234500',
                'base_salary' => 0,
                'role' => $adminRole?->title_ar ?? 'مدير النظام',
                'role_id' => $adminRole?->id,
                'is_active' => true,
            ]
        );
    }
}
