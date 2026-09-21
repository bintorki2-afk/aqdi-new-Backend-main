<?php

namespace Database\Seeders;

use App\Models\ReaEstatType;
use Illuminate\Database\Seeder;

class ReaEstatTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'contract_type' => 'housing'],
            ['name_ar' => 'فيلا', 'name_en' => 'Villa', 'contract_type' => 'housing'],
            ['name_ar' => 'شقة دوبلكس', 'name_en' => 'Duplex Apartment', 'contract_type' => 'housing'],
            ['name_ar' => 'بيت شعبي', 'name_en' => 'Traditional House', 'contract_type' => 'housing'],
            ['name_ar' => 'غرفة مفروشة', 'name_en' => 'Furnished Room', 'contract_type' => 'housing'],
            ['name_ar' => 'استوديو', 'name_en' => 'Studio', 'contract_type' => 'housing'],
            ['name_ar' => 'محل تجاري', 'name_en' => 'Commercial Shop', 'contract_type' => 'commercial'],
            ['name_ar' => 'مكتب', 'name_en' => 'Office', 'contract_type' => 'commercial'],
            ['name_ar' => 'مستودع', 'name_en' => 'Warehouse', 'contract_type' => 'commercial'],
            ['name_ar' => 'عمارة', 'name_en' => 'Building', 'contract_type' => 'commercial'],
        ];

        foreach ($types as $type) {
            ReaEstatType::updateOrCreate(
                ['name_ar' => $type['name_ar'], 'contract_type' => $type['contract_type']],
                $type
            );
        }
    }
}
