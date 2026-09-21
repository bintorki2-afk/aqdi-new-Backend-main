<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-Marketing-Tracking.postman_collection.json
 *
 * Run: php tools/generate_marketing_tracking_postman.php
 */

$output = dirname(__DIR__).'/postman/AQDI-Admin-Marketing-Tracking.postman_collection.json';

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

$periodQuery = [
    ['key' => 'period', 'value' => 'last_30_days'],
    ['key' => 'date_from', 'value' => '2026-08-01', 'disabled' => true],
    ['key' => 'date_to', 'value' => '2026-08-31', 'disabled' => true],
];

$collection = [
    'info' => [
        '_postman_id' => uuidV4(),
        'name' => 'AQDI Admin Marketing Tracking',
        'description' => implode("\n", [
            'UI snapshots for marketing tracking (`/api/admin/marketing-tracking`).',
            'Frontend prompt: prompts/admin-marketing-tracking.md',
            '',
            '1. Import this collection.',
            '2. Set `baseUrl` (e.g. http://localhost:8000).',
            '3. Run **Employee login** — token is saved to `employee_token`.',
            '4. Role needs `analytics.view`.',
            '5. Default period is `last_30_days`. Enable `date_from` + `date_to` for a custom range.',
            '',
            'Sections:',
            '- Overview = ROAS header + widgets (one GET)',
            '- Keywords = ranking table + cards',
            '- Channels = funnel + paid-channel ROI',
        ]),
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000'],
        ['key' => 'employee_token', 'value' => ''],
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
                    'Saves `data.token` to collection variable `employee_token`. Permission on later calls: analytics.view.',
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
            'name' => '1 — Overview (ROAS + widgets)',
            'item' => [
                requestItem(
                    'Overview — last 30 days',
                    'GET',
                    '/marketing-tracking',
                    'Section 3 + 4: summary (roas/spend/revenue/profit), kpis, chart, top_keywords, top_pages, top_campaigns, best_campaign, weakest_campaign.',
                    null,
                    $periodQuery
                ),
                requestItem(
                    'Overview — last 7 days',
                    'GET',
                    '/marketing-tracking',
                    'Same payload, shorter period.',
                    null,
                    [['key' => 'period', 'value' => 'last_7_days']]
                ),
                requestItem(
                    'Overview — custom range',
                    'GET',
                    '/marketing-tracking',
                    'Send date_from and date_to together (YYYY-MM-DD).',
                    null,
                    [
                        ['key' => 'date_from', 'value' => '2026-08-01'],
                        ['key' => 'date_to', 'value' => '2026-08-31'],
                    ]
                ),
            ],
        ],
        [
            'name' => '2 — Keywords ranking',
            'item' => [
                requestItem(
                    'Keywords table',
                    'GET',
                    '/marketing-tracking/keywords',
                    'Section 1: data.summary cards (organic_revenue, organic_clicks, decreased, increased, average_rank, target_keywords) + data.items rows (rank, competition, status, revenue). Ranks are null until Search Console is connected.',
                    null,
                    $periodQuery
                ),
            ],
        ],
        [
            'name' => '3 — Funnel and paid channels',
            'item' => [
                requestItem(
                    'Funnel + channels ROI',
                    'GET',
                    '/marketing-tracking/channels',
                    'Section 2: data.funnel (impressions → clicks → leads → conversions) and data.channels (always google/meta/tiktok/snapchat/twitter with spend, revenue, roas, cac, profit).',
                    null,
                    $periodQuery
                ),
            ],
        ],
        [
            'name' => '4 — Content (service pages + articles)',
            'item' => [
                requestItem(
                    'Service pages list',
                    'GET',
                    '/marketing/service-pages',
                    'Content tab: summary (total/published/drafts) + items. Permission: analytics.view. Not period-scoped.'
                ),
                requestItem(
                    'Create service page',
                    'POST',
                    '/marketing/service-pages',
                    'Body: title, path, target_keyword, status=published|draft|archived. Permission: analytics.create.',
                    [
                        'title' => 'توثيق عقد إيجار سكني',
                        'path' => '/residential',
                        'target_keyword' => 'عقد إيجار سكني',
                        'status' => 'published',
                    ]
                ),
                requestItem(
                    'Update service page',
                    'PUT',
                    '/marketing/service-pages/12',
                    'All fields optional. Permission: analytics.edit.',
                    ['status' => 'published']
                ),
                requestItem(
                    'Delete service page',
                    'DELETE',
                    '/marketing/service-pages/12',
                    'Permission: analytics.delete.'
                ),
                requestItem(
                    'Articles (marketing view)',
                    'GET',
                    '/marketing/articles',
                    'Same blogs as /admin/blogs plus views/leads/attributed_revenue and editorial_queue. CRUD stays on /admin/blogs. Permission: blogs.view.',
                    null,
                    array_merge($periodQuery, [
                        ['key' => 'category', 'value' => 'guides', 'disabled' => true],
                        ['key' => 'status', 'value' => 'published', 'disabled' => true],
                    ])
                ),
            ],
        ],
        [
            'name' => '5 — Reports tab',
            'item' => [
                requestItem(
                    'Report overview',
                    'GET',
                    '/marketing/reports',
                    'Highlights, period comparison, stats cards. Optional channel=google|meta|tiktok|snapchat|twitter|all. Permission: analytics.view.',
                    null,
                    array_merge($periodQuery, [
                        ['key' => 'channel', 'value' => 'all', 'disabled' => true],
                    ])
                ),
                requestItem(
                    'Report channel table',
                    'GET',
                    '/marketing/reports/channels',
                    'Fixed 5 channels + totals row (leads, conversions, spend, revenue, roas, cac, profit).',
                    null,
                    $periodQuery
                ),
                requestItem(
                    'Export report (CSV)',
                    'POST',
                    '/marketing/reports/export',
                    'format: pdf|xlsx|csv|email. File download, or email to the current employee.',
                    [
                        'format' => 'csv',
                        'period' => 'last_30_days',
                        'date_from' => null,
                        'date_to' => null,
                        'channel' => 'all',
                    ]
                ),
                requestItem(
                    'Email report',
                    'POST',
                    '/marketing/reports/export',
                    'Sends CSV to the logged-in employee. Response message: أُرسل التقرير إلى بريدك.',
                    [
                        'format' => 'email',
                        'period' => 'last_30_days',
                        'channel' => 'all',
                    ]
                ),
            ],
        ],
        [
            'name' => 'Related — Google Search Console',
            'item' => [
                requestItem(
                    'Google connection status',
                    'GET',
                    '/seo-google/status',
                    'Needed for keyword ranks and top_pages. Permission: seo_crawl.view.'
                ),
                requestItem(
                    'Connect Google (get auth_url)',
                    'POST',
                    '/seo-google/connect',
                    'Returns data.auth_url. Open it, grant Search Console + Analytics read-only. Permission: seo_crawl.create.',
                    new stdClass()
                ),
                requestItem(
                    'Search Console sites',
                    'GET',
                    '/seo-google/search-console/sites',
                    'List properties; auto-selects when only one site exists.'
                ),
                requestItem(
                    'Select Search Console site',
                    'POST',
                    '/seo-google/search-console/sites',
                    'Body: { "site_url": "https://aqdi.sa/" }',
                    ['site_url' => 'https://aqdi.sa/']
                ),
            ],
        ],
        [
            'name' => 'Related — ad spend',
            'item' => [
                requestItem(
                    'UTM template',
                    'GET',
                    '/reports/marketing/utm-template',
                    'Canonical utm_source values and example tagged URLs.'
                ),
                requestItem(
                    'Import ad spend',
                    'POST',
                    '/reports/marketing/spend',
                    'Manual rows into ad_spend_dailies. Permission: analytics.create.',
                    [
                        'rows' => [[
                            'spent_on' => '2026-08-01',
                            'platform' => 'google',
                            'campaign_id' => '123',
                            'campaign_name' => 'Google Search - High Intent',
                            'spend' => 32500,
                            'currency' => 'SAR',
                            'impressions' => 400000,
                            'clicks' => 4200,
                        ]],
                    ]
                ),
                requestItem(
                    'Sync ad spend from ad accounts',
                    'POST',
                    '/reports/marketing/sync',
                    'Pull spend from configured Google/Meta/TikTok/Snapchat/X credentials.',
                    ['days' => 7, 'platform' => 'google']
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
