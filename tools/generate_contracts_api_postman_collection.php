<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Contracts-API.postman_collection.json
 * from routes/api_v2.php contract routes.
 *
 * Run: php tools/generate_contracts_api_postman_collection.php
 */

$basePath = dirname(__DIR__);

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
    $segments = array_values(array_filter(explode('/', $path)));
    $apiPath = array_merge(['api', 'v2'], $segments);

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

function req(string $name, string $method, string $path, array $opts = []): array
{
    $query = $opts['query'] ?? [];
    $body = $opts['body'] ?? null;
    $method = strtoupper($method);

    $request = [
        'method' => $method,
        'header' => hdr($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)),
        'url' => buildUrl($path, $query),
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

$folders = [
    'Contract creation (steps)' => [
        req('Start contract', 'POST', '/contract/start', [
            'body' => [
                'contract_type' => 'housing',
                'instrument_type' => 'old_handwritten',
                'is_real' => false,
            ],
            'description' => 'Creates a new contract. Returns contract_id and uuid.',
        ]),
        req('Step 1 — property details', 'POST', '/contract/step1', [
            'body' => [
                'id' => '{{contract_id}}',
                'number_of_floors' => 2,
                'property_type_id' => 1,
                'property_usages_id' => 1,
                'number_of_units_in_realestate' => 4,
            ],
            'description' => 'File fields (image_instrument_from_the_front/back) use multipart/form-data in Postman.',
        ]),
        req('Step 2 — address', 'POST', '/contract/step2', [
            'body' => [
                'id' => '{{contract_id}}',
                'property_place_id' => 1,
                'property_city_id' => 1,
                'neighborhood' => 'حي النخيل',
                'street' => 'شارع الملك',
                'building_number' => '12',
                'postal_code' => '12345',
                'extra_figure' => '1234',
            ],
        ]),
        req('Step 3 — owner', 'POST', '/contract/step3', [
            'body' => [
                'id' => '{{contract_id}}',
                'type_dob_property_owner' => 'hijri',
                'name_owner' => 'اسم المالك',
                'property_owner_id_num' => '1234567890',
                'property_owner_dob_day' => '15',
                'property_owner_dob_month' => '06',
                'property_owner_dob_year' => '1410',
                'property_owner_mobile' => '0512345678',
                'property_owner_iban' => 'SA0380000000608010167500',
                'add_legal_agent_of_owner' => '0',
                'notes_edits' => 'ملاحظات التعديلات — حقل اختياري',
            ],
            'description' => 'Optional notes_edits. Agent/owner file fields use multipart.',
        ]),
        req('Step 4 — tenant', 'POST', '/contract/step4', [
            'body' => [
                'id' => '{{contract_id}}',
                'tenant_name' => 'اسم المستأجر',
                'type_tenant_dob' => 'hijri',
                'tenant_id_num' => '1098765432',
                'tenant_dob_day' => '01',
                'tenant_dob_month' => '01',
                'tenant_dob_year' => '1415',
                'tenant_mobile' => '0598765432',
            ],
        ]),
        req('Step 5 — unit details (legacy flat)', 'POST', '/contract/step5', [
            'body' => [
                'id' => '{{contract_id}}',
                'unit_type_id' => 1,
                'unit_usage_id' => 1,
                'unit_number' => '101',
                'floor_number' => 1,
                'unit_area' => '120',
                'kitchen_tank' => 0,
                'furnished' => 0,
                'electricity_meter' => 1,
                'water_meter' => 1,
            ],
            'description' => 'Legacy flat payload (still supported). Prefer units[] — see collection AQDI-Contract-Units-Multi-Unit-API.',
        ]),
        req('Step 5 — multiple units (NEW)', 'POST', '/contract/step5', [
            'body' => [
                'id' => '{{contract_id}}',
                'units' => [
                    [
                        'unit_type_id' => 1,
                        'unit_usage_id' => 1,
                        'unit_number' => '101',
                        'floor_number' => 1,
                        'unit_area' => 120,
                        'split_ac' => 2,
                        'furnished' => true,
                        'electricity_meter' => true,
                        'water_meter' => true,
                    ],
                    [
                        'unit_type_id' => 1,
                        'unit_number' => '102',
                        'floor_number' => 1,
                        'unit_area' => 90,
                        'split_ac' => 1,
                    ],
                ],
            ],
            'description' => 'Creates/links multiple units via contract_units. Response includes units[] + units_count.',
        ]),
        req('Preview doc fee — housing 1y (249)', 'POST', '/contract/doc-fee', [
            'body' => [
                'contract_type' => 'housing',
                'duration_preset' => 'other',
                'duration_years' => 1,
                'duration_months' => 0,
            ],
            'description' => 'معاينة رسوم التوثيق بدون حفظ. سكني: 249 أول سنة، +150 لكل سنة إضافية. تجاري: 349 / +500. أي جزء من سنة = سنة كاملة.',
        ]),
        req('Preview doc fee — housing 1y 1m (399)', 'POST', '/contract/doc-fee', [
            'body' => [
                'contract_type' => 'housing',
                'duration_preset' => 'other',
                'duration_years' => 1,
                'duration_months' => 1,
            ],
        ]),
        req('Preview doc fee — commercial 2y (849)', 'POST', '/contract/doc-fee', [
            'body' => [
                'contract_type' => 'commercial',
                'duration_preset' => 'other',
                'duration_years' => 2,
                'duration_months' => 0,
            ],
        ]),
        req('Preview doc fee — by contract id', 'POST', '/contract/doc-fee', [
            'body' => [
                'id' => '{{contract_id}}',
                'duration_preset' => 'other',
                'duration_years' => 1,
                'duration_months' => 3,
            ],
        ]),
        req('Step 6 — terms & payment (tenant_role_ids)', 'POST', '/contract/step6', [
            'body' => [
                'id' => '{{contract_id}}',
                'type_contract_starting_date' => 'hijri',
                'contract_starting_date_day' => '01',
                'contract_starting_date_month' => '01',
                'contract_starting_date_year' => '1447',
                'contract_term_in_years' => 1,
                'annual_rent_amount_for_the_unit' => 24000,
                'payment_type_id' => 1,
                'conditions' => false,
                'tenant_roles' => true,
                'tenant_role_ids' => [1, 2],
                'additional_terms' => false,
            ],
            'description' => 'Preferred: tenant_role_ids array. Legacy single tenant_role_id still accepted.',
        ]),
        req('Step 6 — مدة أخرى (رسوم إيجار)', 'POST', '/contract/step6', [
            'body' => [
                'id' => '{{contract_id}}',
                'type_contract_starting_date' => 'hijri',
                'contract_starting_date_day' => '01',
                'contract_starting_date_month' => '01',
                'contract_starting_date_year' => '1447',
                'duration_preset' => 'other',
                'duration_years' => 1,
                'duration_months' => 1,
                'annual_rent_amount_for_the_unit' => 24000,
                'payment_type_id' => 1,
                'conditions' => false,
                'tenant_roles' => true,
                'tenant_role_ids' => [1, 2],
                'additional_terms' => false,
            ],
            'description' => 'يحفظ رسوم التوثيق على العقد. الرد يشمل doc_fee و doc_fee_lines.',
        ]),
        req('Step 6 — legacy tenant_role_id', 'POST', '/contract/step6', [
            'body' => [
                'id' => '{{contract_id}}',
                'type_contract_starting_date' => 'hijri',
                'contract_starting_date_day' => '01',
                'contract_starting_date_month' => '01',
                'contract_starting_date_year' => '1447',
                'contract_term_in_years' => 1,
                'annual_rent_amount_for_the_unit' => 24000,
                'payment_type_id' => 1,
                'conditions' => false,
                'tenant_roles' => true,
                'tenant_role_id' => 1,
                'additional_terms' => false,
            ],
        ]),
        req('Set contract draft', 'POST', '/contract/draft', [
            'body' => [
                'id' => '{{contract_id}}',
                'is_draft' => true,
            ],
            'description' => 'Set whether the contract is saved as a draft (true) or not (false). Call after step 6 or when the client is ready to choose.',
        ]),
    ],
    'Uncompleted contracts' => [
        req('Check uncompleted contract', 'GET', '/contract/check-uncompleted-contract', [
            'query' => ['contract_type' => 'housing'],
            'description' => 'Required query `contract_type=housing|commercial`. Incomplete drafts are scoped to that type only. When check=true: contract_id, uuid, step, contract_type.',
        ]),
        req('Get uncompleted contract step', 'POST', '/contract/uncompleted-contract', [
            'body' => ['uuid' => '{{contract_uuid}}'],
            'description' => 'Resume an incomplete contract by uuid. Returns current step plus saved data for all previous steps (step1…step6 resources, same shape as step endpoints). Skips steps 1–2 for lease_renewal/sublease_agreement; skips step 4 for lease_renewal.',
        ]),
    ],
    'Contracts listing' => [
        req('List my contracts', 'GET', '/contracts'),
        req('Show contract', 'GET', '/contracts/{{contract_id}}'),
        req('Delete contract', 'DELETE', '/contracts/{{contract_id}}', [
            'description' => 'Soft-deletes the contract (sets is_delete). Only the contract owner can delete. Completed contracts cannot be deleted.',
        ]),
        req('Get contracts by uuid', 'GET', '/getContracts/{{contract_uuid}}'),
        req('Search contracts', 'GET', '/search/{{search_term}}'),
        req('Financial summary', 'GET', '/financial/{{contract_uuid}}'),
        req('Finance summary (alias)', 'GET', '/finance-summary/{{contract_uuid}}'),
    ],
];

$items = [];
foreach ($folders as $folderName => $requests) {
    $items[] = [
        'name' => $folderName,
        'item' => $requests,
    ];
}

$collectionVars = [
    ['key' => 'baseUrl', 'value' => 'http://localhost:8000', 'type' => 'default'],
    ['key' => 'token', 'value' => '', 'type' => 'secret'],
    ['key' => 'contract_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'contract_uuid', 'value' => '', 'type' => 'default'],
    ['key' => 'search_term', 'value' => '101', 'type' => 'default'],
];

$collection = [
    'info' => [
        '_postman_id' => uuid4(),
        'name' => 'AQDI Contracts API (V2)',
        'description' => "Contract endpoints from routes/api_v2.php.\n\nBase path: `{{baseUrl}}/api/v2`\n\nRequires `auth:sanctum` Bearer token (except public routes).\n\n1. Set `baseUrl` and `token`\n2. Run Start contract → save `contract_id` / `uuid` from response",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        '_exporter_id' => 'aqdi-blade',
    ],
    'item' => $items,
    'variable' => $collectionVars,
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

$collectionPath = $postmanDir.'/AQDI-Contracts-API.postman_collection.json';

file_put_contents(
    $collectionPath,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

$requestCount = array_sum(array_map(fn ($f) => count($f), $folders));

echo "Wrote {$collectionPath}\n";
echo "Folders: ".count($folders).", Requests: {$requestCount}\n";
