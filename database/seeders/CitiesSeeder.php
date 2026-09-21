<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\City;

class CitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $citiesJson = file_get_contents(storage_path('json/citiesP.json'));
        $cities = json_decode($citiesJson, true);
        
        foreach($cities as $city)
        {
            City::query()->updateORCreate([
                 "id"=>$city['city_id'],
                "region_id"=>$city['region_id'],
                 "name_ar"=>$city ['name_ar'],
                "name_en"=>$city['name_en']
            ]);
        }

    }
}