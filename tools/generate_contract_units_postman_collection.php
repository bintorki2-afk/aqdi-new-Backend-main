<?php

declare(strict_types=1);

/**
 * Postman collection: multi-unit contracts (contract_units) + related endpoint changes.
 *
 * Run:
 *   php tools/generate_contract_units_postman_collection.php
 *
 * Output:
 *   postman/AQDI-Contract-Units-Multi-Unit-API.postman_collection.json
 */

$basePath = dirname(__DIR__);
$outDir = $basePath.DIRECTORY_SEPARATOR.'postman';
$outFile = $outDir.DIRECTORY_SEPARATOR.'AQDI-Contract-Units-Multi-Unit-API.postman_collection.json';

if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

function uuid4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function hdr(bool $json = false, bool $bearer = true): array
{
    $h = [
        ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ];
    if ($json) {
        $h[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
    }
    if ($bearer) {
        $h[] = [
            'key' => 'Authorization',
            'value' => 'Bearer {{token}}',
            'type' => 'text',
        ];
    }

    return $h;
}

/**
 * @param  array<string, scalar|null>  $query
 * @return array<string, mixed>
 */
function buildUrl(string $prefix, string $path, array $query = []): array
{
    $path = '/'.trim($path, '/');
    $segments = array_values(array_filter(explode('/', $path)));
    $apiPath = array_merge(['api', $prefix], $segments);

    $queryItems = [];
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $queryItems[] = [
            'key' => (string) $key,
            'value' => (string) $value,
        ];
    }

    $raw = '{{baseUrl}}/'.implode('/', $apiPath);
    if ($queryItems !== []) {
        $raw .= '?'.http_build_query(array_column($queryItems, 'value', 'key'));
    }

    $url = [
        'raw' => $raw,
        'host' => ['{{baseUrl}}'],
        'path' => $apiPath,
    ];

    if ($queryItems !== []) {
        $url['query'] = $queryItems;
    }

    return $url;
}

/**
 * @param  array<string, mixed>  $opts
 * @return array<string, mixed>
 */
function req(string $name, string $method, string $path, array $opts = []): array
{
    $prefix = $opts['prefix'] ?? 'v2';
    $query = $opts['query'] ?? [];
    $body = $opts['body'] ?? null;
    $bearer = $opts['bearer'] ?? true;
    $method = strtoupper($method);

    $request = [
        'method' => $method,
        'header' => hdr($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true), $bearer),
        'url' => buildUrl($prefix, $path, $query),
    ];

    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    $item = [
        'name' => $name,
        'request' => $request,
        'response' => [],
    ];

    if (! empty($opts['description'])) {
        $item['request']['description'] = $opts['description'];
    }

    return $item;
}

$unitA = [
    'unit_type_id' => 1,
    'unit_usage_id' => 1,
    'unit_number' => '101',
    'floor_number' => 1,
    'unit_area' => 120,
    'tootal_rooms' => 3,
    'The_number_of_halls' => 1,
    'The_number_of_kitchens' => 1,
    'The_number_of_toilets' => 2,
    'window_ac' => 0,
    'split_ac' => 2,
    'electricity_meter_number' => 'E-1001',
    'water_meter_number' => 'W-1001',
    'kitchen_tank' => false,
    'furnished' => true,
    'type_furnished' => 'partial',
    'electricity_meter' => true,
    'water_meter' => true,
    'electricity_meter_ownership' => 'owner',
    'water_meter_ownership' => 'tenant',
];

$unitB = [
    'unit_type_id' => 1,
    'unit_usage_id' => 1,
    'unit_number' => '102',
    'floor_number' => 1,
    'unit_area' => 90,
    'tootal_rooms' => 2,
    'split_ac' => 1,
    'kitchen_tank' => false,
    'furnished' => false,
    'electricity_meter' => true,
    'water_meter' => false,
];

$folders = [
    [
        'name' => '1) Contract start',
        'item' => [
            req('Start contract (optional real + unit)', 'POST', '/contract/start', [
                'body' => [
                    'contract_type' => 'housing',
                    'instrument_type' => 'old_handwritten',
                    'is_real' => true,
                    'real_id' => '{{real_estate_id}}',
                    'real_units_id' => '{{unit_id}}',
                ],
                'description' => "Creates contract.\nIf real_units_id is sent, it is also attached in contract_units.\nResponse: contract_id + uuid.",
            ]),
            req('Start contract (no unit yet)', 'POST', '/contract/start', [
                'body' => [
                    'contract_type' => 'housing',
                    'instrument_type' => 'old_handwritten',
                    'is_real' => false,
                ],
            ]),
        ],
    ],
    [
        'name' => '2) Step 5 — multi units (NEW)',
        'item' => [
            req('Step 5 — create multiple NEW units', 'POST', '/contract/step5', [
                'body' => [
                    'id' => '{{contract_id}}',
                    'units' => [$unitA, $unitB],
                ],
                'description' => <<<'MD'
Creates 1+ units in `real_units` and links them via `contract_units`.

Does NOT write unit details into `contracts` columns anymore.
Only advances `step` to 6 and sets legacy `real_units_id` = first unit.

Response includes:
- data.units[]
- data.units_count
- units_count
MD,
            ]),
            req('Step 5 — attach existing unit_ids', 'POST', '/contract/step5', [
                'body' => [
                    'id' => '{{contract_id}}',
                    'unit_ids' => [10, 11],
                ],
                'description' => 'Attach existing real_units owned by the authenticated user.',
            ]),
            req('Step 5 — mix existing + new units', 'POST', '/contract/step5', [
                'body' => [
                    'id' => '{{contract_id}}',
                    'units' => [
                        ['unit_id' => '{{unit_id}}'],
                        $unitB,
                    ],
                ],
                'description' => 'units[].unit_id (or id / real_unit_id) attaches existing unit; other objects create new units.',
            ]),
            req('Step 5 — legacy flat single unit (compat)', 'POST', '/contract/step5', [
                'body' => array_merge([
                    'id' => '{{contract_id}}',
                ], $unitA),
                'description' => 'Old flat payload still works. Backend converts it internally to units[0].',
            ]),
        ],
    ],
    [
        'name' => '3) Contract read (units in response)',
        'item' => [
            req('Show contract (includes units)', 'GET', '/contracts/{{contract_id}}', [
                'description' => 'Response now includes units[] and units_count when loaded.',
            ]),
            req('List my contracts', 'GET', '/contracts'),
            req('Finance summary', 'GET', '/finance-summary/{{contract_uuid}}', [
                'description' => 'Unchanged financially; listed for full contract flow after step5/6.',
            ]),
            req('Financial alias', 'GET', '/financial/{{contract_uuid}}'),
        ],
    ],
    [
        'name' => '4) Units CRUD (related)',
        'item' => [
            req('List units of real estate', 'GET', '/unit/index/{{real_estate_id}}'),
            req('Create standalone unit', 'POST', '/unit/create', [
                'body' => array_merge([
                    'real_estates_units_id' => '{{real_estate_id}}',
                ], $unitA),
                'description' => 'Create unit on property first, then attach with step5 unit_ids / units[].unit_id.',
            ]),
            req('Show unit', 'GET', '/unit/show/{{unit_id}}'),
        ],
    ],
    [
        'name' => '5) Save property (changed)',
        'item' => [
            req('Save property from contract', 'POST', '/save/property', [
                'body' => [
                    'contract_id' => '{{contract_id}}',
                    'name_real_estate' => 'عقار تجريبي',
                ],
                'description' => <<<'MD'
If contract already has units in contract_units:
- those units get real_estates_units_id updated
- no duplicate unit is created from contracts.* fields

Response data includes:
- real_estate
- units_real (primary)
- units (all linked units)
MD,
            ]),
        ],
    ],
    [
        'name' => '6) Admin — contract detail units',
        'item' => [
            req('Admin order show (units[])', 'GET', '/orders/{{contract_id}}', [
                'prefix' => 'admin',
                'description' => 'Admin detail now returns units[] + units_count in addition to legacy unit.',
            ]),
        ],
    ],
];

$collection = [
    'info' => [
        '_postman_id' => uuid4(),
        'name' => 'AQDI — Contract Multi Units (step5 + changes)',
        'description' => <<<'MD'
# Multi-unit contracts

## What changed
- New table: `contract_units` (`contract_id`, `real_unit_id`, `real_estate_id`)
- `POST /api/v2/contract/step5` accepts one or more units
- Unit details are stored in `real_units`, NOT copied into `contracts` columns
- Contract show / admin detail return `units[]`

## Auth
Set collection variables:
- `baseUrl` e.g. `https://your-domain.com`
- `token` user Bearer token (Sanctum)
- `contract_id`, `contract_uuid`, `real_estate_id`, `unit_id`

## Step5 body shapes
1. `units: [ {...}, {...} ]` create multiple
2. `unit_ids: [10,11]` attach existing
3. mix `units: [{unit_id:10}, {...new}]`
4. legacy flat single-unit fields (still supported)
MD,
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost'],
        ['key' => 'token', 'value' => ''],
        ['key' => 'contract_id', 'value' => '1'],
        ['key' => 'contract_uuid', 'value' => ''],
        ['key' => 'real_estate_id', 'value' => '1'],
        ['key' => 'unit_id', 'value' => '1'],
    ],
    'item' => $folders,
];

file_put_contents(
    $outFile,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
);

echo "Wrote: {$outFile}\n";
