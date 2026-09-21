<?php

namespace Database\Seeders;

use App\Models\ReaEstatUsage;
use Illuminate\Database\Seeder;

class ReaEstatUsageSeeder extends Seeder
{
    public function run(): void
    {
        $usages = [
            ['name_ar' => 'سكني', 'name_en' => 'Residential', 'contract_type' => 'housing'],
            ['name_ar' => 'سكني استثماري', 'name_en' => 'Investment Residential', 'contract_type' => 'housing'],
            ['name_ar' => 'تجاري', 'name_en' => 'Commercial', 'contract_type' => 'commercial'],
            ['name_ar' => 'مكتبي', 'name_en' => 'Office', 'contract_type' => 'commercial'],
            ['name_ar' => 'صناعي', 'name_en' => 'Industrial', 'contract_type' => 'commercial'],
            ['name_ar' => 'تجاري استثماري', 'name_en' => 'Investment Commercial', 'contract_type' => 'commercial'],
        ];

        foreach ($usages as $usage) {
            ReaEstatUsage::updateOrCreate(
                ['name_ar' => $usage['name_ar'], 'contract_type' => $usage['contract_type']],
                $usage
            );
        }
    }
}
