<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected ?Client $twilio = null;

    /**
     * Lazily build the Twilio client from config so an unconfigured install
     * never crashes just by resolving this service.
     */
    protected function client(): Client
    {
        if ($this->twilio === null) {
            $this->twilio = new Client(
                (string) config('services.twilio.sid'),
                (string) config('services.twilio.token')
            );
        }

        return $this->twilio;
    }

    public function isConfigured(): bool
    {
        return (string) config('services.twilio.sid') !== ''
            && (string) config('services.twilio.token') !== ''
            && (string) config('services.twilio.from') !== '';
    }

    /**
     * @return string|\Illuminate\Http\JsonResponse Message SID on success.
     */
    public function sendSms($to, $message)
    {
        try {
            $result = $this->client()->messages->create($to, [
                'from' => (string) config('services.twilio.from'),
                'body' => $message,
            ]);

            return $result->sid;
        } catch (\Twilio\Exceptions\RestException $e) {
            return response()->json(['error' => 'Could not send SMS.', 'details' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred.', 'details' => $e->getMessage()], 500);
        }
    }
}
