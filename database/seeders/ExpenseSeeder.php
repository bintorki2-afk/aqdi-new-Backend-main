<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Expense;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::pluck('id')->all();

        $rows = [
            [
                'notes' => 'رسوم توثيق عقود الإيجار خلال هذا الشهر',
                'amount' => 2500.00,
            ],
            [
                'notes' => 'عمولة منصة إيجار لعقود سكنية وتجارية',
                'amount' => 1800.50,
            ],
            [
                'notes' => 'مصاريف تسويق رقمية (إعلانات منصات التواصل)',
                'amount' => 1200.75,
            ],
            [
                'notes' => 'مكافآت لموظفي خدمة العملاء على إنهاء العقود في الوقت المحدد',
                'amount' => 950.00,
            ],
            [
                'notes' => 'مصاريف تطوير النظام وتحسين تجربة المستخدم',
                'amount' => 3200.00,
            ],
            [
                'notes' => 'مصاريف دعم فني للتكامل مع منصة إيجار',
                'amount' => 1450.25,
            ],
        ];

        foreach ($rows as $row) {
            $row['employee_id'] = !empty($employees)
                ? $employees[array_rand($employees)]
                : null;

            Expense::create($row);
        }
    }
}

