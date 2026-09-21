<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['region_id' => 1, 'name_ar' => 'الرياض', 'name_en' => 'Riyadh'],
            ['region_id' => 1, 'name_ar' => 'الخرج', 'name_en' => 'Al-Kharj'],
            ['region_id' => 1, 'name_ar' => 'الدوادمي', 'name_en' => 'Ad-Dawadimi'],
            ['region_id' => 2, 'name_ar' => 'مكة المكرمة', 'name_en' => 'Makkah'],
            ['region_id' => 2, 'name_ar' => 'جدة', 'name_en' => 'Jeddah'],
            ['region_id' => 2, 'name_ar' => 'الطائف', 'name_en' => 'Taif'],
            ['region_id' => 2, 'name_ar' => 'رابغ', 'name_en' => 'Rabigh'],
            ['region_id' => 3, 'name_ar' => 'المدينة المنورة', 'name_en' => 'Madinah'],
            ['region_id' => 3, 'name_ar' => 'ينبع', 'name_en' => 'Yanbu'],
            ['region_id' => 4, 'name_ar' => 'بريدة', 'name_en' => 'Buraidah'],
            ['region_id' => 4, 'name_ar' => 'عنيزة', 'name_en' => 'Unaizah'],
            ['region_id' => 5, 'name_ar' => 'الدمام', 'name_en' => 'Dammam'],
            ['region_id' => 5, 'name_ar' => 'الخبر', 'name_en' => 'Khobar'],
            ['region_id' => 5, 'name_ar' => 'الظهران', 'name_en' => 'Dhahran'],
            ['region_id' => 5, 'name_ar' => 'الأحساء', 'name_en' => 'Al-Ahsa'],
            ['region_id' => 5, 'name_ar' => 'القطيف', 'name_en' => 'Qatif'],
            ['region_id' => 6, 'name_ar' => 'أبها', 'name_en' => 'Abha'],
            ['region_id' => 6, 'name_ar' => 'خميس مشيط', 'name_en' => 'Khamis Mushait'],
            ['region_id' => 7, 'name_ar' => 'تبوك', 'name_en' => 'Tabuk'],
            ['region_id' => 8, 'name_ar' => 'حائل', 'name_en' => 'Hail'],
            ['region_id' => 10, 'name_ar' => 'جازان', 'name_en' => 'Jazan'],
            ['region_id' => 11, 'name_ar' => 'نجران', 'name_en' => 'Najran'],
        ];

        foreach ($cities as $city) {
            City::updateOrCreate(
                [
                    'region_id' => $city['region_id'],
                    'name_ar' => $city['name_ar'],
                ],
                $city
            );
        }
    }
}
