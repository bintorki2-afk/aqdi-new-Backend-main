<?php

namespace App\Services;

use App\Models\SmsLog;
use TaqnyatSms;
use Throwable;

class TaqnyatSmsService
{
    /**
     * Send an SMS via Taqnyat and persist a SmsLog row, including gateway `cost` when present.
     *
     * @return true|false|string True on success, false on empty gateway response, or "SMS Error: ..." on exception.
     */
    public function sendAndLog(
        string $body,
        string $recipients,
        ?string $type = null,
        ?int $userId = null,
        ?string $sender = null,
        ?string $smsId = null,
        ?string $logPhoneNumber = null,
        ?string $logMessage = null
    ): bool|string {
        $sender = $sender ?: (string) config('services.taqnyat.sender', 'AqdiCo');
        $smsId = $smsId ?: (string) config('services.taqnyat.sms_id', '25489');
        $phone = $logPhoneNumber ?: $recipients;
        $logType = $type ?: 'sms';
        $storedMessage = $logMessage ?: $body;

        try {
            $response = (new TaqnyatSms((string) config('services.taqnyat.bearer')))
                ->sendMsg($body, $recipients, $sender, $smsId);

            $sent = $this->isSuccessfulResponse($response);

            SmsLog::create([
                'user_id' => $userId,
                'phone_number' => $phone,
                'message' => $sent ? $storedMessage : 'SMS sending failed',
                'sms_id' => $sent ? $smsId : null,
                'type' => $logType,
                'cost' => $sent ? self::parseCost($response) : null,
                'sent_at' => now(),
            ]);

            return $sent;
        } catch (Throwable $e) {
            SmsLog::create([
                'user_id' => $userId,
                'phone_number' => $phone,
                'message' => 'SMS Error: '.$e->getMessage(),
                'sms_id' => $smsId,
                'type' => $logType,
                'cost' => null,
                'sent_at' => now(),
            ]);

            return 'SMS Error: '.$e->getMessage();
        }
    }

    public static function parseCost(mixed $response): ?float
    {
        $payload = self::normalizeResponse($response);
        if ($payload === null || ! array_key_exists('cost', $payload)) {
            return null;
        }

        if (! is_numeric($payload['cost'])) {
            return null;
        }

        $cost = round((float) $payload['cost'], 4);

        return $cost > 0 ? $cost : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function normalizeResponse(mixed $response): ?array
    {
        if (is_array($response)) {
            return $response;
        }

        if (is_object($response)) {
            return get_object_vars($response);
        }

        if (! is_string($response) || trim($response) === '') {
            return null;
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function isSuccessfulResponse(mixed $response): bool
    {
        if ($response === false || $response === null || $response === '') {
            return false;
        }

        $payload = self::normalizeResponse($response);
        if ($payload === null) {
            return (bool) $response;
        }

        if (isset($payload['statusCode'])) {
            $code = (int) $payload['statusCode'];

            return $code >= 200 && $code < 300;
        }

        return true;
    }
}
