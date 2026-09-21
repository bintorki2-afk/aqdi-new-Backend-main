<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Admin dashboard uses Sanctum personal-access tokens. If a Bearer token is
 * present, skip Sanctum's session (`web`) guard so a public-website User
 * cookie cannot shadow the employee token and look like a permission denial.
 */
class PreferEmployeeBearerToken
{
    public function handle(Request $request, Closure $next)
    {
        if (! filled($request->bearerToken())) {
            return $next($request);
        }

        $originalGuards = config('sanctum.guard');
        config(['sanctum.guard' => []]);

        try {
            return $next($request);
        } finally {
            config(['sanctum.guard' => $originalGuards]);
        }
    }
}
