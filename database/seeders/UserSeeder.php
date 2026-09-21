<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['fname' => 'أحمد', 'lname' => 'العتيبي', 'email' => 'ahmed.user@example.com', 'mobile' => '00966501234501'],
            ['fname' => 'سارة', 'lname' => 'الغامدي', 'email' => 'sara.user@example.com', 'mobile' => '00966501234502'],
            ['fname' => 'عمر', 'lname' => 'الزهراني', 'email' => 'omar.user@example.com', 'mobile' => '00966501234503'],
            ['fname' => 'مريم', 'lname' => 'الدوسري', 'email' => 'maryam.user@example.com', 'mobile' => '00966501234504'],
            ['fname' => 'يوسف', 'lname' => 'الشمري', 'email' => 'yousef.user@example.com', 'mobile' => '00966501234505'],
            ['fname' => 'هند', 'lname' => 'المطيري', 'email' => 'hind.user@example.com', 'mobile' => '00966501234506'],
            ['fname' => 'سعد', 'lname' => 'السهلي', 'email' => 'saad.user@example.com', 'mobile' => '00966501234507'],
            ['fname' => 'لطيفة', 'lname' => 'العمري', 'email' => 'latifa.user@example.com', 'mobile' => '00966501234508'],
            ['fname' => 'راشد', 'lname' => 'الشهري', 'email' => 'rashed.user@example.com', 'mobile' => '00966501234509'],
            ['fname' => 'نادية', 'lname' => 'الحربي', 'email' => 'nadia.user@example.com', 'mobile' => '00966501234510'],
        ];

        foreach ($users as $user) {
            $user['password'] = bcrypt('User@123');
            $user['email_verified_at'] = Carbon::now();
            $user['is_active'] = true;
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
