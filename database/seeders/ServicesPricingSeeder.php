<?php

namespace Database\Seeders;

use App\Models\ServicesPricing;
use Illuminate\Database\Seeder;

class ServicesPricingSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name_ar' => 'توثيق عقد إيجار سكني', 'name_en' => 'Housing contract certification', 'price' => '150', 'contract_type' => 'housing'],
            ['name_ar' => 'توثيق عقد إيجار تجاري', 'name_en' => 'Commercial contract certification', 'price' => '250', 'contract_type' => 'commercial'],
            ['name_ar' => 'استشارة قانونية', 'name_en' => 'Legal consultation', 'price' => '100', 'contract_type' => 'housing'],
            ['name_ar' => 'مراجعة عقد', 'name_en' => 'Contract review', 'price' => '75', 'contract_type' => 'housing'],
            ['name_ar' => 'إعداد عقد مخصص', 'name_en' => 'Custom contract preparation', 'price' => '200', 'contract_type' => 'commercial'],
        ];

        foreach ($services as $service) {
            ServicesPricing::updateOrCreate(
                ['name_ar' => $service['name_ar'], 'contract_type' => $service['contract_type']],
                $service
            );
        }
    }
}
