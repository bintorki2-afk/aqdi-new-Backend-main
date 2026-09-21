<?php

namespace App\Http\Middleware;

use App\Services\AppStatusService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebsiteApiIsOpen
{
    public function __construct(protected AppStatusService $appStatus)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request)) {
            return $next($request);
        }

        if (! $this->appStatus->isWebsiteClient($request)) {
            return $next($request);
        }

        try {
            if ($this->appStatus->isWebsiteOpen()) {
                return $next($request);
            }
        } catch (\Throwable) {
            return $next($request);
        }

        $payload = $this->appStatus->websitePayload();

        return response()->json([
            'message' => trans('api.website_closed'),
            'code' => 503,
            'success' => false,
            'data' => $payload,
        ], 503);
    }

    private function isExempt(Request $request): bool
    {
        $path = trim($request->path(), '/');

        if (str_starts_with($path, 'api/admin')) {
            return true;
        }

        return in_array($path, [
            'api/app-status',
            'api/v2/app-status',
            'api/website-status',
            'api/v2/website-status',
        ], true);
    }
}
