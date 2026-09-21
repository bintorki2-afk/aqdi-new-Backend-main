<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

abstract class BasePaymentService
{
    protected string $baseUrl = '';

    /** @var array<string, string> */
    protected array $headers = [];

    /**
     * Send an HTTP request to the gateway and normalise the response.
     *
     * @param  array<string, mixed>|null  $data
     * @param  string  $bodyFormat  Guzzle body option: "json" or "form_params".
     * @return array{success: bool, status: int, data: array<string, mixed>|null, message: string|null}
     */
    protected function buildRequest(string $method, string $url, ?array $data = null, string $bodyFormat = 'json'): array
    {
        try {
            $response = Http::withHeaders($this->headers)->send($method, $this->baseUrl . $url, [
                $bodyFormat => $data ?? [],
            ]);

            $body = $response->json();

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => is_array($body) ? $body : null,
                'message' => $response->successful() ? null : $this->extractError($body),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'status' => 500,
                'data' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Pull a human-readable error message out of a gateway error body.
     */
    protected function extractError(mixed $body): string
    {
        if (is_array($body)) {
            if (! empty($body['message']) && is_string($body['message'])) {
                return $body['message'];
            }

            if (! empty($body['errors'])) {
                return is_string($body['errors'])
                    ? $body['errors']
                    : (string) json_encode($body['errors']);
            }
        }

        return 'Payment gateway returned an unexpected response.';
    }
}
