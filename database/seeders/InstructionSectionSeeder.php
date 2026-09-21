<?php

namespace Database\Seeders;

use App\Models\InstructionSection;
use App\Support\InstructionSectionDefinitions;
use Illuminate\Database\Seeder;

class InstructionSectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (InstructionSectionDefinitions::predefined() as $definition) {
            InstructionSection::query()->updateOrCreate(
                ['key' => $definition['key']],
                [
                    'title_ar' => $definition['title_ar'],
                    'description_ar' => $definition['description_ar'],
                    'sort_order' => $definition['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
