<?php

namespace Database\Seeders;

use App\Models\UnitUsage;
use Illuminate\Database\Seeder;

class UnitUsageSeeder extends Seeder
{
    public function run(): void
    {
        $usages = [
            ['name_ar' => 'سكني', 'name_en' => 'Residential', 'contract_type' => 'housing'],
            ['name_ar' => 'تجاري', 'name_en' => 'Commercial', 'contract_type' => 'commercial'],
            ['name_ar' => 'مكتبي', 'name_en' => 'Office', 'contract_type' => 'commercial'],
            ['name_ar' => 'مطعم', 'name_en' => 'Restaurant', 'contract_type' => 'commercial'],
            ['name_ar' => 'عيادة', 'name_en' => 'Clinic', 'contract_type' => 'commercial'],
            ['name_ar' => 'صالون', 'name_en' => 'Salon', 'contract_type' => 'commercial'],
        ];

        foreach ($usages as $usage) {
            UnitUsage::updateOrCreate(
                ['name_ar' => $usage['name_ar'], 'contract_type' => $usage['contract_type']],
                $usage
            );
        }
    }
}
