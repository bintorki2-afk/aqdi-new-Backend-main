<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin API token lifetimes
    |--------------------------------------------------------------------------
    |
    | Access tokens last 12 hours. Refresh tokens are persisted separately
    | from Sanctum tokens so they cannot authenticate API requests.
    |
    */
    'access_token_ttl_seconds' => (int) env('ADMIN_ACCESS_TOKEN_TTL_SECONDS', 12 * 60 * 60),

    'refresh_token_ttl_seconds' => (int) env('ADMIN_REFRESH_TOKEN_TTL_SECONDS', 8 * 60 * 60),

    'remembered_refresh_token_ttl_seconds' => (int) env(
        'ADMIN_REMEMBERED_REFRESH_TOKEN_TTL_SECONDS',
        30 * 24 * 60 * 60
    ),
];
