<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        // Every one of the 13 regions (see RegionSeeder) must have at least a
        // few major cities, otherwise the property-address step dead-ends for
        // users in that region (the city dropdown comes back empty). Previously
        // regions 9 (الحدود الشمالية), 12 (الباحة) and 13 (الجوف) had none.
        $cities = [
            // 1 - الرياض
            ['region_id' => 1, 'name_ar' => 'الرياض', 'name_en' => 'Riyadh'],
            ['region_id' => 1, 'name_ar' => 'الخرج', 'name_en' => 'Al-Kharj'],
            ['region_id' => 1, 'name_ar' => 'الدوادمي', 'name_en' => 'Ad-Dawadimi'],
            ['region_id' => 1, 'name_ar' => 'المجمعة', 'name_en' => 'Al-Majmaah'],
            ['region_id' => 1, 'name_ar' => 'الزلفي', 'name_en' => 'Az-Zulfi'],
            // 2 - مكة المكرمة
            ['region_id' => 2, 'name_ar' => 'مكة المكرمة', 'name_en' => 'Makkah'],
            ['region_id' => 2, 'name_ar' => 'جدة', 'name_en' => 'Jeddah'],
            ['region_id' => 2, 'name_ar' => 'الطائف', 'name_en' => 'Taif'],
            ['region_id' => 2, 'name_ar' => 'رابغ', 'name_en' => 'Rabigh'],
            ['region_id' => 2, 'name_ar' => 'القنفذة', 'name_en' => 'Al-Qunfudhah'],
            // 3 - المدينة المنورة
            ['region_id' => 3, 'name_ar' => 'المدينة المنورة', 'name_en' => 'Madinah'],
            ['region_id' => 3, 'name_ar' => 'ينبع', 'name_en' => 'Yanbu'],
            ['region_id' => 3, 'name_ar' => 'العلا', 'name_en' => 'AlUla'],
            ['region_id' => 3, 'name_ar' => 'بدر', 'name_en' => 'Badr'],
            // 4 - القصيم
            ['region_id' => 4, 'name_ar' => 'بريدة', 'name_en' => 'Buraidah'],
            ['region_id' => 4, 'name_ar' => 'عنيزة', 'name_en' => 'Unaizah'],
            ['region_id' => 4, 'name_ar' => 'الرس', 'name_en' => 'Ar-Rass'],
            ['region_id' => 4, 'name_ar' => 'المذنب', 'name_en' => 'Al-Mithnab'],
            // 5 - الشرقية
            ['region_id' => 5, 'name_ar' => 'الدمام', 'name_en' => 'Dammam'],
            ['region_id' => 5, 'name_ar' => 'الخبر', 'name_en' => 'Khobar'],
            ['region_id' => 5, 'name_ar' => 'الظهران', 'name_en' => 'Dhahran'],
            ['region_id' => 5, 'name_ar' => 'الأحساء', 'name_en' => 'Al-Ahsa'],
            ['region_id' => 5, 'name_ar' => 'القطيف', 'name_en' => 'Qatif'],
            ['region_id' => 5, 'name_ar' => 'الجبيل', 'name_en' => 'Jubail'],
            ['region_id' => 5, 'name_ar' => 'حفر الباطن', 'name_en' => 'Hafar Al-Batin'],
            // 6 - عسير
            ['region_id' => 6, 'name_ar' => 'أبها', 'name_en' => 'Abha'],
            ['region_id' => 6, 'name_ar' => 'خميس مشيط', 'name_en' => 'Khamis Mushait'],
            ['region_id' => 6, 'name_ar' => 'بيشة', 'name_en' => 'Bisha'],
            ['region_id' => 6, 'name_ar' => 'النماص', 'name_en' => 'An-Namas'],
            // 7 - تبوك
            ['region_id' => 7, 'name_ar' => 'تبوك', 'name_en' => 'Tabuk'],
            ['region_id' => 7, 'name_ar' => 'ضباء', 'name_en' => 'Duba'],
            ['region_id' => 7, 'name_ar' => 'الوجه', 'name_en' => 'Al-Wajh'],
            ['region_id' => 7, 'name_ar' => 'تيماء', 'name_en' => 'Tayma'],
            // 8 - حائل
            ['region_id' => 8, 'name_ar' => 'حائل', 'name_en' => 'Hail'],
            ['region_id' => 8, 'name_ar' => 'بقعاء', 'name_en' => 'Baqaa'],
            // 9 - الحدود الشمالية
            ['region_id' => 9, 'name_ar' => 'عرعر', 'name_en' => 'Arar'],
            ['region_id' => 9, 'name_ar' => 'رفحاء', 'name_en' => 'Rafha'],
            ['region_id' => 9, 'name_ar' => 'طريف', 'name_en' => 'Turaif'],
            // 10 - جازان
            ['region_id' => 10, 'name_ar' => 'جازان', 'name_en' => 'Jazan'],
            ['region_id' => 10, 'name_ar' => 'صبيا', 'name_en' => 'Sabya'],
            ['region_id' => 10, 'name_ar' => 'أبو عريش', 'name_en' => 'Abu Arish'],
            // 11 - نجران
            ['region_id' => 11, 'name_ar' => 'نجران', 'name_en' => 'Najran'],
            ['region_id' => 11, 'name_ar' => 'شرورة', 'name_en' => 'Sharurah'],
            // 12 - الباحة
            ['region_id' => 12, 'name_ar' => 'الباحة', 'name_en' => 'Al-Bahah'],
            ['region_id' => 12, 'name_ar' => 'بلجرشي', 'name_en' => 'Baljurashi'],
            ['region_id' => 12, 'name_ar' => 'المندق', 'name_en' => 'Al-Mandaq'],
            // 13 - الجوف
            ['region_id' => 13, 'name_ar' => 'سكاكا', 'name_en' => 'Sakaka'],
            ['region_id' => 13, 'name_ar' => 'القريات', 'name_en' => 'Al-Qurayyat'],
            ['region_id' => 13, 'name_ar' => 'دومة الجندل', 'name_en' => 'Dumat Al-Jandal'],
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
