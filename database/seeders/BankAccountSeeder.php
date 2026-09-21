<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'bank_name_ar' => 'البنك الأهلي السعودي',
                'bank_name_en' => 'Al Ahli Bank',
                'bank_account_name_ar' => 'شركة عقدي للتأجير',
                'bank_account_name_en' => 'Aqdi Rental Company',
                'bank_account_number' => '1234567890123456',
                'iban_number' => 'SA0380000000608010167519',
            ],
            [
                'bank_name_ar' => 'بنك الرياض',
                'bank_name_en' => 'Riyad Bank',
                'bank_account_name_ar' => 'شركة عقدي للتأجير',
                'bank_account_name_en' => 'Aqdi Rental Company',
                'bank_account_number' => '9876543210987654',
                'iban_number' => 'SA2120000000608010167519',
            ],
            [
                'bank_name_ar' => 'البنك السعودي الفرنسي',
                'bank_name_en' => 'Banque Saudi Fransi',
                'bank_account_name_ar' => 'شركة عقدي للتأجير',
                'bank_account_name_en' => 'Aqdi Rental Company',
                'bank_account_number' => '5555666677778888',
                'iban_number' => 'SA0550000000608010167519',
            ],
        ];

        foreach ($accounts as $account) {
            BankAccount::updateOrCreate(
                ['iban_number' => $account['iban_number']],
                $account
            );
        }
    }
}
