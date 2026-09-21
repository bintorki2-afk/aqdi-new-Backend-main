<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name_ar' => 'دفع شهري', 'name_en' => 'Monthly Payment', 'contract_type' => 'housing'],
            ['name_ar' => 'دفع ربع سنوي', 'name_en' => 'Quarterly Payment', 'contract_type' => 'housing'],
            ['name_ar' => 'دفع نصف سنوي', 'name_en' => 'Semi-Annual Payment', 'contract_type' => 'housing'],
            ['name_ar' => 'دفع سنوي', 'name_en' => 'Annual Payment', 'contract_type' => 'housing'],
            ['name_ar' => 'دفع مقدماً', 'name_en' => 'Advance Payment', 'contract_type' => 'housing'],
            ['name_ar' => 'دفع شهري تجاري', 'name_en' => 'Monthly Commercial', 'contract_type' => 'commercial'],
            ['name_ar' => 'دفع سنوي تجاري', 'name_en' => 'Annual Commercial', 'contract_type' => 'commercial'],
        ];

        foreach ($types as $type) {
            PaymentType::updateOrCreate(
                ['name_ar' => $type['name_ar'], 'contract_type' => $type['contract_type']],
                $type
            );
        }
    }
}
