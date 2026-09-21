<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Units-API.postman_collection.json
 *
 * Standalone units API (API V2): create one or more units on an existing property.
 *
 * Run: php tools/generate_units_postman_collection.php
 */

$basePath = dirname(__DIR__);

const UNITS_COLLECTION_ID = 'b4e7c2a9-1d58-4f03-9c6e-7a2b8d4e5f10';

function uuid4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function hdr(bool $json = false): array
{
    $h = [
        ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ];
    if ($json) {
        $h[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
    }

    return $h;
}

/**
 * @param  array<string, scalar|null>  $query
 * @return array<string, mixed>
 */
function buildUrl(string $path, array $query = []): array
{
    $path = '/'.trim($path, '/');
    $segments = array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));
    $apiPath = array_merge(['api', 'v2'], $segments);

    $url = [
        'raw' => '{{baseUrl}}/'.implode('/', $apiPath),
        'host' => ['{{baseUrl}}'],
        'path' => $apiPath,
    ];

    if ($query !== []) {
        $queryItems = [];
        foreach ($query as $key => $value) {
            $queryItems[] = ['key' => (string) $key, 'value' => (string) $value];
        }
        $url['query'] = $queryItems;
        $url['raw'] .= '?'.http_build_query(array_column($queryItems, 'value', 'key'));
    }

    return $url;
}

/**
 * @param  array<string, mixed>  $opts
 * @return array<string, mixed>
 */
function req(string $name, string $method, string $path, array $opts = []): array
{
    $method = strtoupper($method);
    $body = $opts['body'] ?? null;
    $public = (bool) ($opts['public'] ?? false);

    $request = [
        'method' => $method,
        'header' => hdr($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)),
        'url' => buildUrl($path, $opts['query'] ?? []),
    ];

    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    if ($public) {
        $request['auth'] = ['type' => 'noauth'];
    }

    if (! empty($opts['description'])) {
        $request['description'] = $opts['description'];
    }

    $item = [
        'name' => $name,
        'request' => $request,
        'response' => [],
    ];

    if (! empty($opts['event'])) {
        $item['event'] = $opts['event'];
    }

    return $item;
}

/**
 * @param  list<array<string, mixed>>  $items
 * @return array<string, mixed>
 */
function folder(string $name, array $items, string $description = ''): array
{
    $folder = [
        'name' => $name,
        'item' => $items,
    ];
    if ($description !== '') {
        $folder['description'] = $description;
    }

    return $folder;
}

$saveTokenScript = [
    [
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                'const json = pm.response.json();',
                'const token = json?.data?.token || json?.token;',
                'if (token) { pm.collectionVariables.set("token", token); }',
            ],
        ],
    ],
];

$saveUnitIdScript = [
    [
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                'const json = pm.response.json();',
                'const unitId = json?.data?.created?.[0]?.id || json?.data?.id;',
                'if (unitId) { pm.collectionVariables.set("unit_id", String(unitId)); }',
            ],
        ],
    ],
];

$housingUnit = [
    'contract_type' => 'housing',
    'unit_type_id' => 1,
    'unit_usage_id' => 1,
    'unit_number' => '101',
    'floor_number' => 1,
    'unit_area' => 120,
    'tootal_rooms' => 3,
    'The_number_of_toilets' => 2,
    'The_number_of_kitchens' => 1,
    'split_ac' => 0,
    'window_ac' => 0,
    'kitchen_tank' => false,
    'furnished' => false,
    'electricity_meter' => false,
    'water_meter' => false,
];

$housingUnitTwo = array_merge($housingUnit, [
    'unit_number' => '102',
    'unit_area' => 90,
    'tootal_rooms' => 2,
    'The_number_of_toilets' => 1,
    'split_ac' => 1,
    'kitchen_tank' => true,
    'electricity_meter' => true,
]);

$commercialUnit = [
    'contract_type' => 'commercial',
    'unit_type_id' => 8,
    'unit_usage_id' => 2,
    'unit_number' => 'C-1',
    'floor_number' => 0,
    'unit_area' => 80,
    'tootal_rooms' => 0,
    'The_number_of_toilets' => 1,
    'The_number_of_kitchens' => 0,
    'split_ac' => 2,
    'window_ac' => 0,
    'kitchen_tank' => false,
    'furnished' => false,
    'electricity_meter' => true,
    'water_meter' => true,
];

$items = [
    folder('0. Auth', [
        req('Login', 'POST', '/auth/login', [
            'public' => true,
            'body' => [
                'mobile' => '0512345678',
                'password' => 'password123',
            ],
            'description' => 'Saves `token`. Run this first.',
            'event' => $saveTokenScript,
        ]),
    ], 'Bearer token for the rest of the collection.'),

    folder('1. Lookups', [
        req('Unit Types — Housing', 'GET', '/units-types', [
            'public' => true,
            'query' => ['contract_type' => 'housing'],
            'description' => 'نوع الوحدة — سكني. `contract_type=housing`. Use returned `id` as `unit_type_id`.',
        ]),
        req('Unit Types — Commercial', 'GET', '/units-types', [
            'public' => true,
            'query' => ['contract_type' => 'commercial'],
            'description' => 'نوع الوحدة — تجاري. `contract_type=commercial`.',
        ]),
        req('Unit Usages — Housing', 'GET', '/units-usage', [
            'public' => true,
            'query' => ['contract_type' => 'housing'],
            'description' => 'استخدام الوحدة — سكني. Use returned `id` as `unit_usage_id`.',
        ]),
        req('Unit Usages — Commercial', 'GET', '/units-usage', [
            'public' => true,
            'query' => ['contract_type' => 'commercial'],
            'description' => 'استخدام الوحدة — تجاري.',
        ]),
    ], 'Dropdowns for the unit form. Public, no auth. Filter by `contract_type` (`housing` | `commercial`).'),

    folder('2. Create', [
        req('Create One Unit', 'POST', '/unit/create', [
            'body' => [
                'real_estates_units_id' => '{{real_estate_id}}',
                'units' => [$housingUnit],
            ],
            'description' => <<<'MD'
Create a single residential unit (`units` with 1 item).

Required per unit: `unit_type_id`, `unit_usage_id`, `unit_number`, `floor_number`, `unit_area`, `tootal_rooms`.

`contract_type`: `housing` (سكني) or `commercial` (تجاري). If omitted, inherited from the property.

Kitchen cabinets (`kitchen_tank` or `kitchen_cabinets`) require `The_number_of_kitchens` >= 1.

Saves `unit_id` from `data.created[0].id`.
MD,
            'event' => $saveUnitIdScript,
        ]),
        req('Create Multiple Units', 'POST', '/unit/create', [
            'body' => [
                'real_estates_units_id' => '{{real_estate_id}}',
                'units' => [$housingUnit, $housingUnitTwo],
            ],
            'description' => <<<'MD'
Create 2+ units in one request (max 50). Same endpoint as Create One Unit.

Response:
- `data.created` — units created in this call
- `data.created_count`
- `data.units` — all units on the property
- `data.units_count`

Saves `unit_id` from the first created unit.
MD,
            'event' => $saveUnitIdScript,
        ]),
        req('Create Commercial Unit', 'POST', '/unit/create', [
            'body' => [
                'real_estates_units_id' => '{{real_estate_id}}',
                'units' => [$commercialUnit],
            ],
            'description' => 'تجاري. `unit_type_id` / `unit_usage_id` must belong to `contract_type=commercial` (see Lookups).',
            'event' => $saveUnitIdScript,
        ]),
    ], 'POST /api/v2/unit/create — one or more units on an existing property (`real_estates_units_id`).'),

    folder('3. Read', [
        req('List Units', 'GET', '/unit/index/{{real_estate_id}}', [
            'description' => 'All units on the property owned by the authenticated user.',
        ]),
        req('List All Units', 'GET', '/unit/all/{{real_estate_id}}', [
            'description' => 'Same payload as List Units.',
        ]),
        req('Show Unit', 'GET', '/unit/show/{{unit_id}}', [
            'description' => 'Unit details. Must belong to the authenticated user.',
        ]),
    ], 'GET /unit/index/{propertyId}, /unit/all/{propertyId}, /unit/show/{unitId}'),

    folder('4. Update', [
        req('Update Unit', 'POST', '/unit/update/{{unit_id}}', [
            'body' => [
                'unit_number' => '103',
                'floor_number' => 2,
                'unit_area' => 130,
                'tootal_rooms' => 4,
                'The_number_of_toilets' => 2,
                'The_number_of_kitchens' => 1,
                'split_ac' => 2,
                'window_ac' => 0,
                'kitchen_tank' => true,
                'furnished' => true,
                'electricity_meter' => true,
                'water_meter' => false,
            ],
            'description' => 'Partial update. Send only fields to change.',
        ]),
    ], 'POST /unit/update/{id}'),

    folder('5. Delete', [
        req('Delete Unit', 'DELETE', '/unit/delete/{{unit_id}}', [
            'description' => 'Deletes the unit.',
        ]),
    ], 'DELETE /unit/delete/{id}'),
];

$collection = [
    'info' => [
        '_postman_id' => UNITS_COLLECTION_ID,
        'name' => 'AQDI Units API',
        'description' => <<<'MD'
وحدات العقار — API V2 only (`POST /api/v2/unit/create` creates **one or more** units).

**Base:** `{{baseUrl}}/api/v2`  
**Auth:** Bearer `{{token}}`  
**Parent property:** set `real_estate_id` first (property must belong to the logged-in user).

### Quick start
1. Set `baseUrl` (e.g. `http://localhost:8000`)
2. Run **0. Auth → Login**
3. Run **1. Lookups** and copy `unit_type_id` / `unit_usage_id`
4. Set `real_estate_id`
5. Run **2. Create → Create One Unit** or **Create Multiple Units**

### Form → body
| UI | Field |
|---|---|
| سكني / تجاري | `contract_type`: `housing` / `commercial` |
| نوع الوحدة | `unit_type_id` |
| استخدام الوحدة | `unit_usage_id` |
| رقم طابق الوحدة | `floor_number` |
| رقم الوحدة | `unit_number` |
| مساحة الوحدة | `unit_area` |
| عدد الغرف | `tootal_rooms` |
| عدد دورات المياه | `The_number_of_toilets` |
| عدد المطابخ | `The_number_of_kitchens` |
| مكيف سبليت | `split_ac` |
| مكيف شباك | `window_ac` |
| خزائن المطبخ | `kitchen_tank` or `kitchen_cabinets` |
| مؤثثة | `furnished` |
| عداد كهرباء | `electricity_meter` |
| عداد مياه | `water_meter` |

Generated by `php tools/generate_units_postman_collection.php`
MD,
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        '_exporter_id' => 'aqdi-blade',
    ],
    'item' => $items,
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000', 'type' => 'default'],
        ['key' => 'token', 'value' => '', 'type' => 'secret'],
        ['key' => 'real_estate_id', 'value' => '1', 'type' => 'default'],
        ['key' => 'unit_id', 'value' => '1', 'type' => 'default'],
    ],
    'auth' => [
        'type' => 'bearer',
        'bearer' => [
            ['key' => 'token', 'value' => '{{token}}', 'type' => 'string'],
        ],
    ],
];

$postmanDir = $basePath.'/postman';
if (! is_dir($postmanDir)) {
    mkdir($postmanDir, 0755, true);
}

$collectionPath = $postmanDir.'/AQDI-Units-API.postman_collection.json';

file_put_contents(
    $collectionPath,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

$requestCount = 0;
$stack = $items;
while ($stack !== []) {
    $node = array_pop($stack);
    if (isset($node['request'])) {
        $requestCount++;
    } elseif (isset($node['item']) && is_array($node['item'])) {
        foreach ($node['item'] as $child) {
            $stack[] = $child;
        }
    }
}

echo "Wrote {$collectionPath}\n";
echo "Requests: {$requestCount}\n";
