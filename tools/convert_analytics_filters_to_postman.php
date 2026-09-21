<?php

declare(strict_types=1);

/**
 * Converts postman/admin-analytics-api-filters.en.json → Postman Collection v2.1
 * Run: php tools/convert_analytics_filters_to_postman.php
 */

$basePath = dirname(__DIR__);
$source = $basePath.'/postman/admin-analytics-api-filters.en.json';
$output = $basePath.'/postman/AQDI-Admin-Analytics-API.postman_collection.json';

$spec = json_decode(file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);

function buildUrl(string $adminPath, array $query = []): array
{
    $adminPath = '/'.trim($adminPath, '/');
    $segments = array_values(array_filter(explode('/', $adminPath)));
    $path = array_merge(['api', 'admin'], $segments);
    $queryItems = [];
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $queryItems[] = ['key' => (string) $key, 'value' => (string) $value];
    }
    $raw = '{{baseUrl}}/'.implode('/', $path);
    if ($queryItems !== []) {
        $raw .= '?'.http_build_query(array_column($queryItems, 'value', 'key'));
    }
    $url = ['raw' => $raw, 'host' => ['{{baseUrl}}'], 'path' => $path];
    if ($queryItems !== []) {
        $url['query'] = $queryItems;
    }

    return $url;
}

function normalizePath(string $path): string
{
    return str_replace(
        ['{contractStatusId}', '{imageId}'],
        ['{{contract_status_id}}', '{{instruction_image_id}}'],
        $path
    );
}

function defaultQueryForParam(string $name): ?string
{
    return match ($name) {
        'limit' => '10',
        'per_page' => '20',
        'period' => 'today',
        'created_at' => 'month',
        'page' => '1',
        default => null,
    };
}

/**
 * @param  array<string, mixed>  $endpoint
 */
function buildRequest(array $endpoint): array
{
    $method = strtoupper($endpoint['method'] ?? 'GET');
    $path = normalizePath($endpoint['path'] ?? '/');
    $path = str_replace('{id}', '{{refund_id}}', $path);
    if (str_contains($path, 'instruction-sections')) {
        $path = str_replace('{{refund_id}}', '{{instruction_section_id}}', $path);
    }
    if (str_contains($path, '/orders/')) {
        $path = str_replace('{{refund_id}}', '{{contract_id}}', $path);
    }

    $query = [];
    $params = $endpoint['query_parameters'] ?? [];
    if (is_array($params)) {
        foreach ($params as $param) {
            if (is_string($param)) {
                $def = defaultQueryForParam($param);
                if ($def !== null) {
                    $query[$param] = $def;
                }
            } elseif (is_array($param) && isset($param['name'])) {
                $query[$param['name']] = (string) ($param['default'] ?? defaultQueryForParam($param['name']) ?? '');
            }
        }
    }

    $headers = [['key' => 'Accept', 'value' => 'application/json', 'type' => 'text']];
    $body = null;

    if (($endpoint['auth'] ?? '') === 'Bearer employee_token') {
        $auth = [
            'type' => 'bearer',
            'bearer' => [['key' => 'token', 'value' => '{{employee_token}}', 'type' => 'string']],
        ];
    } else {
        $auth = null;
    }

    if ($method === 'POST' && isset($endpoint['body']) && is_array($endpoint['body'])) {
        $headers[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
        $sample = [];
        foreach ($endpoint['body'] as $key => $meta) {
            if (! is_array($meta)) {
                continue;
            }
            $sample[$key] = match ($meta['type'] ?? 'string') {
                'integer', 'number' => $meta['example'] ?? ($key === 'contract_id' ? '{{contract_id}}' : 1),
                'boolean' => true,
                default => $meta['example'] ?? null,
            };
        }
        $body = [
            'mode' => 'raw',
            'raw' => json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    if (($endpoint['content_type'] ?? '') === 'multipart/form-data') {
        $headers = array_filter($headers, fn ($h) => ($h['key'] ?? '') !== 'Content-Type');
        $body = [
            'mode' => 'formdata',
            'formdata' => [
                ['key' => 'image', 'type' => 'file', 'src' => []],
                ['key' => 'title_ar', 'value' => 'عنوان', 'type' => 'text'],
                ['key' => 'sort_order', 'value' => '1', 'type' => 'text'],
            ],
        ];
    }

    $request = [
        'method' => $method,
        'header' => $headers,
        'url' => buildUrl($path, $query),
    ];

    if ($auth !== null) {
        $request['auth'] = $auth;
    }
    if ($body !== null) {
        $request['body'] = $body;
    }
    if (! empty($endpoint['description'])) {
        $request['description'] = $endpoint['description'];
    }

    return [
        'name' => $endpoint['name'] ?? $method.' '.$path,
        'request' => $request,
    ];
}

/**
 * @param  list<array<string, mixed>>  $endpoints
 * @return list<array<string, mixed>>
 */
function endpointsToItems(array $endpoints): array
{
    $items = [];
    foreach ($endpoints as $ep) {
        $items[] = buildRequest($ep);
    }

    return $items;
}

/**
 * @param  list<array<string, mixed>>  $endpoints
 * @return list<array<string, mixed>>
 */
function groupEndpoints(array $endpoints): array
{
    $groups = [];
    foreach ($endpoints as $ep) {
        $group = $ep['group'] ?? 'other';
        $groups[$group][] = $ep;
    }

    $folders = [];
    foreach ($groups as $name => $eps) {
        $folders[] = [
            'name' => ucfirst(str_replace('_', ' ', $name)),
            'item' => endpointsToItems($eps),
        ];
    }

    return $folders;
}

$allEndpoints = array_merge(
    $spec['endpoints'] ?? [],
    $spec['orders_endpoints'] ?? [],
    $spec['instruction_sections_endpoints'] ?? []
);

if (isset($spec['payments_filter']) && is_array($spec['payments_filter'])) {
    $pf = $spec['payments_filter'];
    $allEndpoints[] = [
        'group' => 'payments',
        'name' => 'List payments',
        'method' => $pf['method'] ?? 'GET',
        'path' => $pf['path'] ?? '/payments',
        'query_parameters' => $pf['query_parameters'] ?? [],
        'description' => $pf['description'] ?? null,
    ];
}

$collection = [
    'info' => [
        '_postman_id' => 'aqdi-admin-analytics-api',
        'name' => 'AQDI Admin — Analytics, Orders, Refunds & Instructions',
        'description' => "Generated from admin-analytics-api-filters.en.json\n\n"
            ."**Do not import** `admin-analytics-api-filters.en.json` into Postman — it is API reference only.\n"
            ."Import this file instead.\n\n"
            .($spec['description'] ?? ''),
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'contract_id', 'value' => '1'],
        ['key' => 'refund_id', 'value' => '1'],
        ['key' => 'contract_status_id', 'value' => '2'],
        ['key' => 'instruction_section_id', 'value' => '1'],
        ['key' => 'instruction_image_id', 'value' => '1'],
    ],
    'item' => array_merge(
        groupEndpoints($spec['endpoints'] ?? []),
        [
            ['name' => 'Orders', 'item' => endpointsToItems($spec['orders_endpoints'] ?? [])],
            ['name' => 'Instruction sections', 'item' => endpointsToItems($spec['instruction_sections_endpoints'] ?? [])],
            ['name' => 'Payments', 'item' => endpointsToItems(
                isset($spec['payments_filter']) ? [[
                    'name' => 'List payments',
                    'method' => 'GET',
                    'path' => '/payments',
                    'query_parameters' => [
                        ['name' => 'filter', 'default' => 'month'],
                        ['name' => 'per_page', 'default' => 20],
                    ],
                ]] : []
            )],
        ]
    ),
];

file_put_contents(
    $output,
    json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n"
);

echo "Written: {$output}\n";
