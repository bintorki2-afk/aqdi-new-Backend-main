<?php

namespace Database\Seeders;

use App\Models\WebsiteImage;
use App\Support\WebsiteImageDefinitions;
use Illuminate\Database\Seeder;

class WebsiteImageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (WebsiteImageDefinitions::all() as $row) {
            WebsiteImage::query()->updateOrCreate(
                ['key' => $row['key']],
                array_merge(['is_active' => true], $row)
            );
        }
    }
}
