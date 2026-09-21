<?php

$base = dirname(__DIR__);

$modules = [
    'Analytics' => 'postman/AQDI-Admin-Analytics-API.postman_collection.json',
    'Employees' => 'postman/AQDI-Admin-Employees-API.postman_collection.json',
    'Roles' => 'postman/AQDI-Admin-Roles-API.postman_collection.json',
    'Coupons' => 'postman/AQDI-Admin-Coupons-API.postman_collection.json',
    'Message Alerts' => 'postman/AQDI-Admin-Message-Alerts-API.postman_collection.json',
];

$items = [];
$variables = [
    ['key' => 'baseUrl', 'value' => 'http://localhost:8000'],
];
$seenVarKeys = ['baseUrl'];

foreach ($modules as $folderName => $relativePath) {
    $path = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    foreach ($data['variable'] ?? [] as $variable) {
        if (! in_array($variable['key'], $seenVarKeys, true)) {
            $variables[] = $variable;
            $seenVarKeys[] = $variable['key'];
        }
    }

    $items[] = [
        'name' => $folderName,
        'item' => $data['item'] ?? [],
    ];
}

// Reorganize Analytics folder
foreach ($items as &$section) {
    if ($section['name'] !== 'Analytics') {
        continue;
    }

    $legacyFolder = [
        'name' => 'Legacy / Full dashboard',
        'item' => [
            makeGetRequest('Get all analytics', '{{baseUrl}}/api/admin/analytics/all'),
            makeGetRequest('Dashboard analytics alias', '{{baseUrl}}/api/admin/dashboard-analytics'),
            makeGetRequest('Analytics (legacy)', '{{baseUrl}}/api/admin/analytics'),
        ],
    ];

    $customerItems = [];
    $otherItems = [];

    foreach ($section['item'] as $entry) {
        if (isset($entry['item'])) {
            $otherItems[] = $entry;
            continue;
        }

        $name = $entry['name'] ?? '';
        if (
            str_contains($name, 'العملاء')
            || str_contains($name, 'نشاط')
            || str_contains($name, 'كل التحليلات')
        ) {
            $customerItems[] = $entry;
            continue;
        }

        $otherItems[] = $entry;
    }

    $section['item'] = array_merge(
        [$legacyFolder],
        $customerItems !== [] ? [['name' => 'تحليلات العملاء', 'item' => $customerItems]] : [],
        $otherItems
    );
}
unset($section);

$collection = [
    'info' => [
        '_postman_id' => 'aqdi-admin-complete-api',
        'name' => 'AQDI Admin — Complete API',
        'description' => "All admin APIs in one collection.\n\n**Modules:** Analytics, Employees, Roles, Coupons, Message Alerts\n\n**Variables:** baseUrl, employee_token, employee_id, role_id, coupon_id, message_alert_section_id, message_alert_section_item_id, message_alert_id\n\n**Employees:** use Bearer {{employee_token}} after Login.",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => $variables,
    'item' => $items,
];

$outputPath = $base . '/postman/AQDI-Admin-Complete.postman_collection.json';
file_put_contents(
    $outputPath,
    json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
);

// Also refresh standalone analytics file (organized)
file_put_contents(
    $base . '/postman/AQDI-Admin-Analytics-API.postman_collection.json',
    json_encode(
        [
            'info' => [
                '_postman_id' => 'aqdi-admin-analytics-api',
                'name' => 'AQDI Admin — Analytics API',
                'description' => $collection['info']['description'],
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                ['key' => 'baseUrl', 'value' => 'http://localhost:8000'],
                ['key' => 'contract_status_id', 'value' => '2'],
            ],
            'item' => $items[0]['item'] ?? [],
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) . "\n"
);

echo "Written: {$outputPath}\n";

function makeGetRequest(string $name, string $url): array
{
    return [
        'name' => $name,
        'request' => [
            'method' => 'GET',
            'header' => [
                ['key' => 'Accept', 'value' => 'application/json'],
            ],
            'url' => $url,
        ],
    ];
}
