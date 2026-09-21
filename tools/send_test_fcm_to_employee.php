<?php

use App\Models\Employee;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$email = $argv[1] ?? 'mohammed@aqdi.com';

$employee = Employee::query()->where('email', $email)->first();

if (! $employee) {
    fwrite(STDERR, "Employee not found: {$email}\n");
    exit(1);
}

echo "Employee: #{$employee->id} {$employee->name} <{$employee->email}>\n";
echo 'Active: '.($employee->is_active ? 'yes' : 'no')."\n";
echo 'FCM token: '.(filled($employee->fcm_token) ? (substr($employee->fcm_token, 0, 20).'...') : 'MISSING')."\n";

if (! filled($employee->fcm_token)) {
    fwrite(STDERR, "No fcm_token saved for this employee. Login with fcm_token first.\n");
    exit(2);
}

$firebase = app(FirebaseNotificationService::class);
$title = 'اختبار إشعار';
$body = 'هذا إشعار تجريبي من عقدي';

$firebase->sendToToken(
    (string) $employee->fcm_token,
    $title,
    $body,
    [
        'type' => 'test_notification',
        'employee_id' => (string) $employee->id,
        'email' => (string) $employee->email,
    ]
);

echo "Notification sent successfully.\n";
