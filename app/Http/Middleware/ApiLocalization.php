<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLocalization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->localeFromRequest($request));

        return $next($request);
    }

    private function localeFromRequest(Request $request): string
    {
        foreach ([
            $request->query('locale'),
            $request->header('X-localization'),
            $request->header('X-Locale'),
        ] as $candidate) {
            $resolved = $this->normalizeLocale(is_string($candidate) ? $candidate : null);
            if ($resolved) {
                return $resolved;
            }
        }

        $accept = (string) $request->header('Accept-Language', '');
        foreach (explode(',', $accept) as $part) {
            $code = trim(explode(';', $part)[0]);
            $resolved = $this->normalizeLocale($code);
            if ($resolved) {
                return $resolved;
            }
        }

        return 'ar';
    }

    private function normalizeLocale(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $value = strtolower(str_replace('_', '-', trim($value)));

        if (str_starts_with($value, 'ar')) {
            return 'ar';
        }

        if (str_starts_with($value, 'en')) {
            return 'en';
        }

        return null;
    }
}
