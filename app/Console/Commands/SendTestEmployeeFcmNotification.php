<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\FirebaseNotificationService;
use Illuminate\Console\Command;

class SendTestEmployeeFcmNotification extends Command
{
    protected $signature = 'fcm:test-employee {email=mohammed@aqdi.com}';

    protected $description = 'Send a test Firebase notification to an employee by email';

    public function handle(FirebaseNotificationService $firebase): int
    {
        $email = (string) $this->argument('email');
        $employee = Employee::query()->where('email', $email)->first();

        if (! $employee) {
            $this->error("Employee not found: {$email}");

            return self::FAILURE;
        }

        $this->info("Employee: #{$employee->id} {$employee->name} <{$employee->email}>");
        $this->info('Active: '.($employee->is_active ? 'yes' : 'no'));

        if (! filled($employee->fcm_token)) {
            $this->error('No fcm_token saved for this employee. Login with fcm_token first.');

            return self::FAILURE;
        }

        $this->info('FCM token: '.substr((string) $employee->fcm_token, 0, 24).'...');

        $firebase->sendToToken(
            (string) $employee->fcm_token,
            'اختبار إشعار',
            'هذا إشعار تجريبي من عقدي',
            [
                'type' => 'test_notification',
                'employee_id' => (string) $employee->id,
                'email' => (string) $employee->email,
            ]
        );

        $this->info('Notification sent successfully.');

        return self::SUCCESS;
    }
}
