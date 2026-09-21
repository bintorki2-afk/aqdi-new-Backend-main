<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends unhandled exceptions to Sentry over the raw ingest (envelope) API, so
 * no composer package / build change is required. Wrapped in try/catch so
 * reporting can never break the request itself.
 *
 * Uses the same Sentry project as the website (shared key). Backend events are
 * tagged platform=php so they are distinguishable from the frontend.
 */
class SentryReporter
{
    private const KEY = '8fc81c7ba7714593e1e81590960f5274';
    private const HOST = 'o4512124723593216.ingest.us.sentry.io';
    private const PROJECT_ID = '4512124752166916';

    /** @return string|null the Sentry event id (reference), or null on failure */
    public static function capture(Throwable $e): ?string
    {
        try {
            $eventId = str_replace('-', '', (string) Str::uuid());

            $frames = [];
            foreach (array_slice($e->getTrace(), 0, 25) as $t) {
                $frames[] = [
                    'filename' => $t['file'] ?? '[internal]',
                    'lineno' => (int) ($t['line'] ?? 0),
                    'function' => ($t['class'] ?? '').($t['type'] ?? '').($t['function'] ?? ''),
                ];
            }
            $frames[] = [
                'filename' => $e->getFile(),
                'lineno' => $e->getLine(),
                'function' => 'throw',
            ];
            $frames = array_reverse($frames); // Sentry wants oldest-first

            $event = [
                'event_id' => $eventId,
                'timestamp' => now()->toIso8601String(),
                'platform' => 'php',
                'level' => 'error',
                'logger' => 'laravel',
                'server_name' => gethostname() ?: 'railway',
                'environment' => app()->environment(),
                'release' => 'aqdi-backend',
                'tags' => ['side' => 'backend'],
                'exception' => [
                    'values' => [[
                        'type' => get_class($e),
                        'value' => $e->getMessage(),
                        'stacktrace' => ['frames' => $frames],
                    ]],
                ],
            ];

            try {
                if (function_exists('request') && request()) {
                    $event['request'] = [
                        'url' => request()->fullUrl(),
                        'method' => request()->method(),
                    ];
                }
            } catch (Throwable $ignore) {
                // no request context (console, etc.)
            }

            $dsn = 'https://'.self::KEY.'@'.self::HOST.'/'.self::PROJECT_ID;
            $body = json_encode(['event_id' => $eventId, 'sent_at' => now()->toIso8601String(), 'dsn' => $dsn])."\n"
                .json_encode(['type' => 'event', 'content_type' => 'application/json'])."\n"
                .json_encode($event)."\n";

            Http::withHeaders([
                'X-Sentry-Auth' => 'Sentry sentry_version=7, sentry_client=aqdi-laravel/1.0, sentry_key='.self::KEY,
            ])
                ->withBody($body, 'application/x-sentry-envelope')
                ->timeout(4)
                ->post('https://'.self::HOST.'/api/'.self::PROJECT_ID.'/envelope/');

            return $eventId;
        } catch (Throwable $inner) {
            return null; // never let reporting break the app
        }
    }
}
