<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-SEO-Crawl.postman_collection.json
 *
 * Run: php tools/generate_seo_crawl_postman.php
 */

$output = dirname(__DIR__).'/postman/AQDI-Admin-SEO-Crawl.postman_collection.json';

function uuidV4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0F | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3F | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * @param  array<int, array{key: string, value: string, disabled?: bool}>  $query
 * @return array<string, mixed>
 */
function url(string $adminPath, array $query = []): array
{
    $adminPath = '/'.ltrim($adminPath, '/');
    $pathSegments = array_values(array_filter(explode('/', trim($adminPath, '/'))));

    $result = [
        'raw' => '{{baseUrl}}/api/admin'.$adminPath,
        'host' => ['{{baseUrl}}'],
        'path' => array_merge(['api', 'admin'], $pathSegments),
    ];

    if ($query !== []) {
        $result['query'] = $query;
        $qs = [];
        foreach ($query as $item) {
            if (($item['disabled'] ?? false) === true) {
                continue;
            }
            $value = (string) $item['value'];
            $qs[] = rawurlencode($item['key']).'='.(str_contains($value, '{{') ? $value : rawurlencode($value));
        }
        if ($qs !== []) {
            $result['raw'] .= '?'.implode('&', $qs);
        }
    }

    return $result;
}

/**
 * @return list<array<string, string>>
 */
function headers(bool $bearer = true, bool $json = false): array
{
    $headers = [
        ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ];

    if ($json) {
        $headers[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
    }

    if ($bearer) {
        $headers[] = ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'];
    }

    return $headers;
}

/**
 * @param  array<string, mixed>|object|null  $body
 * @param  array<int, array{key: string, value: string, disabled?: bool}>  $query
 * @param  list<string>|null  $testScript
 * @return array<string, mixed>
 */
function requestItem(
    string $name,
    string $method,
    string $path,
    string $description,
    mixed $body = null,
    array $query = [],
    ?array $testScript = null,
    bool $bearer = true
): array {
    $method = strtoupper($method);
    $request = [
        'method' => $method,
        'header' => headers($bearer, $body !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)),
        'url' => url($path, $query),
        'description' => $description,
    ];

    if ($body !== null) {
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    $item = [
        'name' => $name,
        'request' => $request,
        'response' => [],
    ];

    if ($testScript !== null) {
        $item['event'] = [[
            'listen' => 'test',
            'script' => [
                'type' => 'text/javascript',
                'exec' => $testScript,
            ],
        ]];
    }

    return $item;
}

$saveToken = [
    'const json = pm.response.json();',
    'const token = json?.data?.token ?? json?.token;',
    'if (token) { pm.collectionVariables.set("employee_token", token); }',
];

$saveRunId = [
    'const json = pm.response.json();',
    'const id = json?.data?.id;',
    'if (id) { pm.collectionVariables.set("seo_crawl_run_id", String(id)); }',
];

$collection = [
    'info' => [
        '_postman_id' => uuidV4(),
        'name' => 'AQDI Admin SEO Crawl',
        'description' => implode("\n", [
            'Technical crawl and site audit for aqdi.sa (`/api/admin/seo-crawl`).',
            '',
            '1. Import this collection.',
            '2. Set `baseUrl` (e.g. http://localhost:8000 or https://aqdi.sa).',
            '3. Run **Employee login** — token is saved to `employee_token`.',
            '4. The employee role needs `seo_crawl.view` and `seo_crawl.create`.',
            '5. **Run crawl** returns 202 (`queued`). Poll **Dashboard** until `status` is `completed` or `stopped`.',
            '6. **Stop crawl** cancels the in-progress scan.',
            '',
            'Statuses: never_run | queued | running | completed | stopped | failed',
        ]),
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'seo_crawl_run_id', 'value' => ''],
    ],
    'auth' => [
        'type' => 'bearer',
        'bearer' => [
            ['key' => 'token', 'value' => '{{employee_token}}', 'type' => 'string'],
        ],
    ],
    'item' => [
        [
            'name' => 'Auth',
            'item' => [
                requestItem(
                    'Employee login (save token)',
                    'POST',
                    '/employees/login',
                    'Saves `data.token` to collection variable `employee_token`.',
                    [
                        'email' => 'admin@example.com',
                        'password' => 'password',
                    ],
                    [],
                    $saveToken,
                    false
                ),
            ],
        ],
        [
            'name' => 'Crawl',
            'item' => [
                requestItem(
                    'Run crawl',
                    'POST',
                    '/seo-crawl/run',
                    'Start a technical crawl of aqdi.sa. Returns 202 with status `queued`. Optional body: url, max_pages. 409 if a scan is already running. Saves `data.id` to `seo_crawl_run_id`. Same as POST /seo-crawl.',
                    [
                        'url' => 'https://aqdi.sa',
                        'max_pages' => 400,
                    ],
                    [],
                    $saveRunId
                ),
                requestItem(
                    'Run crawl (POST /seo-crawl alias)',
                    'POST',
                    '/seo-crawl',
                    'Alias of POST /seo-crawl/run.',
                    new stdClass(),
                    [],
                    $saveRunId
                ),
                requestItem(
                    'Stop crawl',
                    'POST',
                    '/seo-crawl/stop',
                    'Stop the current scan (or `run_id` if sent). Returns 409 if nothing is running.',
                    [
                        'run_id' => '{{seo_crawl_run_id}}',
                    ]
                ),
                requestItem(
                    'Stop crawl (latest in progress)',
                    'POST',
                    '/seo-crawl/stop',
                    'Stops whichever scan is queued/running. Omit run_id.',
                    new stdClass()
                ),
            ],
        ],
        [
            'name' => 'Results',
            'item' => [
                requestItem(
                    'Dashboard (latest scan)',
                    'GET',
                    '/seo-crawl',
                    'Summary cards (indexed / healthy / broken / on-page) and the 12 category tiles. Poll until status is completed or stopped.'
                ),
                requestItem(
                    'Dashboard by run_id',
                    'GET',
                    '/seo-crawl',
                    'Same dashboard for a specific run.',
                    null,
                    [
                        ['key' => 'run_id', 'value' => '{{seo_crawl_run_id}}'],
                    ]
                ),
                requestItem(
                    'Issues table',
                    'GET',
                    '/seo-crawl/issues',
                    'Paginated issue rows: page, problem, severity. Optional type, severity, search, run_id.',
                    null,
                    [
                        ['key' => 'page', 'value' => '1'],
                        ['key' => 'per_page', 'value' => '20'],
                        ['key' => 'run_id', 'value' => '{{seo_crawl_run_id}}', 'disabled' => true],
                        ['key' => 'type', 'value' => 'page_404', 'disabled' => true],
                        ['key' => 'severity', 'value' => 'high', 'disabled' => true],
                        ['key' => 'search', 'value' => 'blog', 'disabled' => true],
                    ]
                ),
                requestItem(
                    'Issues — 404 pages',
                    'GET',
                    '/seo-crawl/issues',
                    'Filter: type=page_404.',
                    null,
                    [
                        ['key' => 'type', 'value' => 'page_404'],
                        ['key' => 'severity', 'value' => 'high'],
                        ['key' => 'per_page', 'value' => '50'],
                    ]
                ),
                requestItem(
                    'Issues — missing titles',
                    'GET',
                    '/seo-crawl/issues',
                    'Filter: type=missing_title.',
                    null,
                    [
                        ['key' => 'type', 'value' => 'missing_title'],
                        ['key' => 'per_page', 'value' => '50'],
                    ]
                ),
                requestItem(
                    'Issues — broken links',
                    'GET',
                    '/seo-crawl/issues',
                    'Filter: type=broken_link.',
                    null,
                    [
                        ['key' => 'type', 'value' => 'broken_link'],
                        ['key' => 'per_page', 'value' => '50'],
                    ]
                ),
            ],
        ],
        [
            'name' => 'Google Search Console / Analytics',
            'item' => [
                requestItem(
                    'Connection status',
                    'GET',
                    '/seo-google/status',
                    'Shows whether a real Google account is linked (email only, no tokens).'
                ),
                requestItem(
                    'Connect Google (get auth_url)',
                    'POST',
                    '/seo-google/connect',
                    'Returns auth_url. Open it in the browser, sign in with the Google account that owns Search Console, then Google redirects back to the admin frontend (?google_seo=connected). Read-only scopes only.',
                    new stdClass()
                ),
                requestItem(
                    'Disconnect Google',
                    'POST',
                    '/seo-google/disconnect',
                    'Removes the stored Google tokens from Aqdi.',
                    new stdClass()
                ),
            ],
        ],
        [
            'name' => 'Search Console data',
            'item' => [
                requestItem(
                    'Overview',
                    'GET',
                    '/seo-google/search-console',
                    'Totals for the selected Search Console site. Optional from/to (Y-m-d). Defaults to the last 28 days.',
                    null,
                    [
                        ['key' => 'from', 'value' => '2026-08-01'],
                        ['key' => 'to', 'value' => '2026-08-28'],
                    ]
                ),
                requestItem(
                    'List sites',
                    'GET',
                    '/seo-google/search-console/sites',
                    'Search Console properties on the connected Google account. Auto-selects when only one site exists.'
                ),
                requestItem(
                    'Select site',
                    'POST',
                    '/seo-google/search-console/sites',
                    'Persist the property used for reports. Body: { "site_url": "https://aqdi.sa/" }',
                    ['site_url' => 'https://aqdi.sa/']
                ),
                requestItem(
                    'Queries',
                    'GET',
                    '/seo-google/search-console/queries',
                    'Top search queries. Optional from, to, limit, start_row.',
                    null,
                    [
                        ['key' => 'from', 'value' => '2026-08-01'],
                        ['key' => 'to', 'value' => '2026-08-28'],
                        ['key' => 'limit', 'value' => '25'],
                    ]
                ),
                requestItem(
                    'Pages',
                    'GET',
                    '/seo-google/search-console/pages',
                    'Top pages by Search Console performance.',
                    null,
                    [
                        ['key' => 'from', 'value' => '2026-08-01'],
                        ['key' => 'to', 'value' => '2026-08-28'],
                    ]
                ),
                requestItem(
                    'Countries',
                    'GET',
                    '/seo-google/search-console/countries',
                    'Performance by country (ISO code, e.g. sau).'
                ),
                requestItem(
                    'Devices',
                    'GET',
                    '/seo-google/search-console/devices',
                    'Performance by device (MOBILE / DESKTOP / TABLET).'
                ),
                requestItem(
                    'Dates',
                    'GET',
                    '/seo-google/search-console/dates',
                    'Daily time series for the selected range.'
                ),
                requestItem(
                    'Sitemaps',
                    'GET',
                    '/seo-google/search-console/sitemaps',
                    'Sitemaps submitted in Search Console for the selected site.'
                ),
            ],
        ],
    ],
];

if (! is_dir(dirname($output))) {
    mkdir(dirname($output), 0777, true);
}

file_put_contents(
    $output,
    json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
);

echo "Wrote {$output}\n";
