<?php

namespace Database\Seeders;

use App\Models\ContractPeriod;
use Illuminate\Database\Seeder;

class ContractPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $periods = [
            [
                'period' => 'شهري',
                'note_ar' => 'عقد إيجار شهري',
                'note_en' => 'Monthly rental contract',
                'contract_type' => 'housing',
                'price' => 150,
            ],
            [
                'period' => 'ربع سنوي',
                'note_ar' => 'عقد إيجار ربع سنوي',
                'note_en' => 'Quarterly rental contract',
                'contract_type' => 'housing',
                'price' => 400,
            ],
            [
                'period' => 'نصف سنوي',
                'note_ar' => 'عقد إيجار نصف سنوي',
                'note_en' => 'Semi-annual rental contract',
                'contract_type' => 'housing',
                'price' => 750,
            ],
            [
                'period' => 'سنوي',
                'note_ar' => 'عقد إيجار سنوي',
                'note_en' => 'Annual rental contract',
                'contract_type' => 'housing',
                'price' => 1400,
            ],
            [
                'period' => 'شهري',
                'note_ar' => 'عقد إيجار تجاري شهري',
                'note_en' => 'Monthly commercial rental',
                'contract_type' => 'commercial',
                'price' => 250,
            ],
            [
                'period' => 'ربع سنوي',
                'note_ar' => 'عقد إيجار تجاري ربع سنوي',
                'note_en' => 'Quarterly commercial rental',
                'contract_type' => 'commercial',
                'price' => 700,
            ],
            [
                'period' => 'سنوي',
                'note_ar' => 'عقد إيجار تجاري سنوي',
                'note_en' => 'Annual commercial rental',
                'contract_type' => 'commercial',
                'price' => 2500,
            ],
        ];

        foreach ($periods as $period) {
            ContractPeriod::updateOrCreate(
                [
                    'period' => $period['period'],
                    'contract_type' => $period['contract_type'],
                ],
                $period
            );
        }
    }
}
