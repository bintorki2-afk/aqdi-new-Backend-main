<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $managerRole = Role::where('name', 'manager')->first();
        $customerServiceRole = Role::where('name', 'customer_service')->first();
        $receiverRole = Role::where('name', 'receiver')->first();

        $employees = [
            [
                'name' => 'محمد العلي',
                'email' => 'mohammed@aqdi.com',
                'password' => bcrypt('Employee@123'),
                'phone' => '966501111001',
                'base_salary' => 8000,
                'role' => 'مدير فرع',
                'role_id' => $managerRole?->id,
                'is_active' => true,
            ],
            [
                'name' => 'فاطمة الحربي',
                'email' => 'fatima@aqdi.com',
                'password' => bcrypt('Employee@123'),
                'phone' => '966501111002',
                'base_salary' => 6000,
                'role' => 'موظفة خدمة عملاء',
                'role_id' => $customerServiceRole?->id,
                'is_active' => true,
            ],
            [
                'name' => 'خالد الدوسري',
                'email' => 'khalid@aqdi.com',
                'password' => bcrypt('Employee@123'),
                'phone' => '966501111003',
                'base_salary' => 5500,
                'role' => 'موظف استلام',
                'role_id' => $receiverRole?->id,
                'is_active' => true,
            ],
            [
                'name' => 'نورة السعيد',
                'email' => 'noura@aqdi.com',
                'password' => bcrypt('Employee@123'),
                'phone' => '966501111004',
                'base_salary' => 6000,
                'role' => 'موظفة خدمة عملاء',
                'role_id' => $customerServiceRole?->id,
                'is_active' => true,
            ],
            [
                'name' => 'عبدالله القحطاني',
                'email' => 'abdullah@aqdi.com',
                'password' => bcrypt('Employee@123'),
                'phone' => '966501111005',
                'base_salary' => 5000,
                'role' => 'موظف استلام',
                'role_id' => $receiverRole?->id,
                'is_active' => true,
            ],
        ];

        foreach ($employees as $emp) {
            Employee::updateOrCreate(
                ['email' => $emp['email']],
                $emp
            );
        }
    }
}
