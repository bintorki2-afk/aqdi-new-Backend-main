<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;
 use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;

class RegionsSeeder extends Seeder
{
    public function run(): void
    {
        $regionsJson = file_get_contents(storage_path('json/regionsP.json'));
        $regions = json_decode($regionsJson, true);
        foreach ($regions as $region) {
            Region::updateOrCreate(
                ['id' => $region['region_id']], 
                [
                    'name_ar' => $region['name_ar'],
                    'name_en' => $region['name_en']
                ]
            );
        }
    }
}