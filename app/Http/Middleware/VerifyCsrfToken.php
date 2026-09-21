<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
   
   
    
    protected $except = [
        'api/callback/*',
        '/status/*/success',
        '/status/*/error',
        'callback',
        'return',
        /*
         * Admin JSON API (api/admin/*) uses the `api` group, which includes
         * Sanctum EnsureFrontendRequestsAreStateful. Same-origin POSTs from the
         * panel would otherwise require X-XSRF-TOKEN. Token-based clients skip
         * that flow; exempt this prefix so create/update endpoints work.
         */
        'api/admin/*',
    ];
}
