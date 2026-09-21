<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingContractSeeder extends Seeder
{
    /**
     * Seed the setting_contracts table so the deed/document-type selector
     * on the website (GET /api/v2/setting-contracts) has options.
     * Idempotent: uses updateOrInsert keyed on the unique instrument_type.
     */
    public function run(): void
    {
        $rows = [
            ['electronic', 'صك إلكتروني (وزارة العدل)'],
            ['electronic_deed_from_the_ministry_of_justice', 'صك إلكتروني من وزارة العدل'],
            ['electronic_tax_register', 'سجل عقاري إلكتروني'],
            ['old_handwritten', 'صك ورقي (يدوي)'],
            ['strong_argument', 'حجة استحكام'],
            ['sale_agreement', 'عقد/ورقة مبايعة'],
            ['economic_cities_authority_suspended', 'هيئة المدن الاقتصادية'],
            ['sublease_agreement', 'عقد إيجار من الباطن'],
            ['lease_renewal', 'تجديد عقد إيجار'],
            ['property_ownership_owner_are_deceased', 'وثيقة ملكية - المالك متوفّى'],
            ['property_ownership_owner_is_endowment', 'وثيقة ملكية - وقف'],
        ];

        $now = now();

        foreach ($rows as $r) {
            DB::table('setting_contracts')->updateOrInsert(
                ['instrument_type' => $r[0]],
                [
                    'realestate' => 1,
                    'contract' => 1,
                    'label' => $r[1],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
