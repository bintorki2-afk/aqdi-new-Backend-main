<?php

namespace Database\Seeders;

use App\Models\UnitType;
use Illuminate\Database\Seeder;

class UnitTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name_ar' => 'استوديو', 'name_en' => 'Studio', 'contract_type' => 'housing', 'rooms' => 'NoRoom'],
            ['name_ar' => 'غرفة واحدة', 'name_en' => '1 Bedroom', 'contract_type' => 'housing', 'rooms' => 'Room'],
            ['name_ar' => 'غرفتين', 'name_en' => '2 Bedrooms', 'contract_type' => 'housing', 'rooms' => 'Room'],
            ['name_ar' => 'ثلاث غرف', 'name_en' => '3 Bedrooms', 'contract_type' => 'housing', 'rooms' => 'Room'],
            ['name_ar' => 'أربع غرف', 'name_en' => '4 Bedrooms', 'contract_type' => 'housing', 'rooms' => 'Room'],
            ['name_ar' => 'خمس غرف وأكثر', 'name_en' => '5+ Bedrooms', 'contract_type' => 'housing', 'rooms' => 'Room'],
            ['name_ar' => 'بدون غرف', 'name_en' => 'No Rooms', 'contract_type' => 'commercial', 'rooms' => 'NoRoom'],
            ['name_ar' => 'مكتب', 'name_en' => 'Office', 'contract_type' => 'commercial', 'rooms' => 'NoRoom'],
        ];

        foreach ($types as $type) {
            UnitType::updateOrCreate(
                ['name_ar' => $type['name_ar'], 'contract_type' => $type['contract_type']],
                $type
            );
        }
    }
}
