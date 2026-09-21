<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['id' => 1, 'name_ar' => 'الرياض', 'name_en' => 'Riyadh'],
            ['id' => 2, 'name_ar' => 'مكة المكرمة', 'name_en' => 'Makkah'],
            ['id' => 3, 'name_ar' => 'المدينة المنورة', 'name_en' => 'Madinah'],
            ['id' => 4, 'name_ar' => 'القصيم', 'name_en' => 'Al-Qassim'],
            ['id' => 5, 'name_ar' => 'الشرقية', 'name_en' => 'Eastern Province'],
            ['id' => 6, 'name_ar' => 'عسير', 'name_en' => 'Asir'],
            ['id' => 7, 'name_ar' => 'تبوك', 'name_en' => 'Tabuk'],
            ['id' => 8, 'name_ar' => 'حائل', 'name_en' => 'Hail'],
            ['id' => 9, 'name_ar' => 'الحدود الشمالية', 'name_en' => 'Northern Borders'],
            ['id' => 10, 'name_ar' => 'جازان', 'name_en' => 'Jazan'],
            ['id' => 11, 'name_ar' => 'نجران', 'name_en' => 'Najran'],
            ['id' => 12, 'name_ar' => 'الباحة', 'name_en' => 'Al-Bahah'],
            ['id' => 13, 'name_ar' => 'الجوف', 'name_en' => 'Al-Jouf'],
        ];

        foreach ($regions as $region) {
            Region::updateOrCreate(
                ['id' => $region['id']],
                ['name_ar' => $region['name_ar'], 'name_en' => $region['name_en']]
            );
        }
    }
}
