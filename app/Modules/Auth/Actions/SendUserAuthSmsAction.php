<?php

namespace App\Modules\Auth\Actions;

use App\Models\SmsLog;
use App\Modules\Auth\Support\AuthMobile;
use App\Services\TaqnyatSmsService;
use App\Services\TwilioService;
use Illuminate\Support\Facades\Log;

class SendUserAuthSmsAction
{
    public function execute($body, $recipients, $sender, $smsId, $type = null, ?string $logMessage = null, ?int $userId = null)
    {
        return app(TaqnyatSmsService::class)->sendAndLog(
            $body,
            $recipients,
            $type,
            $userId ?? auth()->id(),
            $sender,
            $smsId,
            AuthMobile::normalizeSaudiMobile((string) $recipients),
            $logMessage
        );
    }

    /**
     * Send an OTP SMS through the configured driver.
     *
     * OTP_DRIVER controls delivery: "log" (default outside production) writes the
     * code to the log + sms_logs so a developer can log in with no SMS account;
     * "taqnyat" / "twilio" send a real message. A real driver whose credentials
     * are missing safely degrades to the log driver so the flow never breaks.
     *
     * @return bool|string True on success, or an error string on failure.
     */
    public function sendOtp(string $body, string $recipients, string $type, ?int $userId = null): bool|string
    {
        return match ($this->resolveDriver()) {
            'twilio' => $this->sendViaTwilio($body, $recipients, $type, $userId),
            'taqnyat' => $this->execute(
                $body,
                $recipients,
                (string) config('services.taqnyat.sender', 'AqdiCo'),
                (string) config('services.taqnyat.sms_id', '25489'),
                $type,
                'OTP sent',
                $userId
            ),
            default => $this->logOtp($body, $recipients, $type, $userId),
        };
    }

    /**
     * Resolve the effective SMS driver, falling back to "log" when a real
     * provider is selected but its credentials are not configured.
     */
    private function resolveDriver(): string
    {
        $driver = strtolower(trim((string) config('otp.driver', '')));

        if ($driver === '') {
            $driver = app()->environment('production') ? 'taqnyat' : 'log';
        }

        if ($driver === 'taqnyat' && (string) config('services.taqnyat.bearer', '') === '') {
            return 'log';
        }

        if ($driver === 'twilio'
            && ((string) config('services.twilio.sid', '') === ''
                || (string) config('services.twilio.token', '') === '')
        ) {
            return 'log';
        }

        return in_array($driver, ['log', 'taqnyat', 'twilio'], true) ? $driver : 'log';
    }

    /**
     * TEST/LOCAL delivery: log the OTP and persist the full message (which
     * contains the plain code) in sms_logs so it can be retrieved for login.
     */
    private function logOtp(string $body, string $recipients, string $type, ?int $userId = null): bool
    {
        $formattedMobile = AuthMobile::normalizeSaudiMobile((string) $recipients) ?: (string) $recipients;

        Log::info('OTP (test-mode, no SMS sent)', [
            'phone_number' => $formattedMobile,
            'type' => $type,
            'message' => $body,
        ]);

        SmsLog::create([
            'user_id' => $userId ?? auth()->id(),
            'phone_number' => $formattedMobile,
            // Store the real body so the plain OTP is retrievable in test mode.
            'message' => $body,
            'sms_id' => 'test-mode',
            'type' => $type,
            'cost' => null,
            'sent_at' => now(),
        ]);

        return true;
    }

    private function sendViaTwilio(string $body, string $recipients, string $type, ?int $userId = null): bool|string
    {
        $formattedMobile = AuthMobile::normalizeSaudiMobile((string) $recipients) ?: (string) $recipients;
        $result = app(TwilioService::class)->sendSms($formattedMobile, $body);
        $sent = is_string($result) && $result !== '';

        SmsLog::create([
            'user_id' => $userId ?? auth()->id(),
            'phone_number' => $formattedMobile,
            'message' => $sent ? 'OTP sent' : 'SMS sending failed',
            'sms_id' => $sent ? $result : null,
            'type' => $type,
            'cost' => null,
            'sent_at' => now(),
        ]);

        return $sent ? true : trans('api.error_sending_sms');
    }

    public function recentSmsLog(string $type, string $mobile, int $minutes = 2): ?SmsLog
    {
        return SmsLog::query()
            ->whereIn('phone_number', AuthMobile::lookupVariants($mobile))
            ->where('type', $type)
            ->where('sent_at', '>=', now()->subMinutes($minutes))
            ->first();
    }

    public function logBlockedResend(?int $userId, string $formattedMobile, string $otpType): void
    {
        SmsLog::create([
            'user_id' => $userId,
            'phone_number' => $formattedMobile,
            'message' => 'Resend blocked: cooldown not expired',
            'type' => $otpType,
            'sms_id' => null,
            'sent_at' => now(),
        ]);
    }
}
