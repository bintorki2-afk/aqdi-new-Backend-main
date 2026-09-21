<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Seo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // Regions & Cities (foundation data)
        $this->call([RegionSeeder::class, CitySeeder::class]);

        // Contract & Real Estate reference data
        $this->call([
            ContractStatusSeeder::class,
            ContractPeriodSeeder::class,
            ReaEstatTypeSeeder::class,
            ReaEstatUsageSeeder::class,
            UnitTypeSeeder::class,
            UnitUsageSeeder::class,
        ]);

        // Payment & Bank
        $this->call([
            PaymentTypeSeeder::class,
            BankAccountSeeder::class,
            ServicesPricingSeeder::class,
        ]);

        // Content & Settings
        $this->call([
            QuestionSeeder::class,
            InstructionSectionSeeder::class,
            WebsiteImageSeeder::class,
            SettingsSeeder::class,
        ]);

        // Roles & Users
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
        $this->call([
            AdminSeeder::class,
            EmployeeSeeder::class,
            UserSeeder::class,
        ]);

        // Blogs
        $this->call([BlogSeeder::class]);

        // Dashboard analytics data (contracts, payments, visitors, etc.)
        $this->call([AnalysisSeeder::class]);

        // Rich realistic contracts (full wizard fields, optional payments)
        $this->call([ContractRealDataSeeder::class]);

        // App config
        Seo::updateOrCreate(
            ['email' => 'seo@email.com'],
            [
                'name' => 'seo',
                'mobile' => '201068389295',
                'password' => 'seo123456',
                'is_seo' => true,
            ]
        );

        $account = Account::first();
        if (! $account) {
            Account::create(['valueContract' => 55]);
        } else {
            $account->update(['valueContract' => 55]);
        }

        Schema::enableForeignKeyConstraints();
    }
}
