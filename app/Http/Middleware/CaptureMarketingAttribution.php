<?php

namespace App\Http\Middleware;

use App\Services\Marketing\AttributionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureMarketingAttribution
{
    public function __construct(
        protected AttributionService $attribution
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->attribution->persistFirstTouch($request);

        return $next($request);
    }
}
