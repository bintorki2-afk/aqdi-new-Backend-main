<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-Popup-Contracts-API.postman_collection.json
 * Run: php tools/generate_popup_contracts_postman.php
 */

$output = dirname(__DIR__).'/postman/AQDI-Admin-Popup-Contracts-API.postman_collection.json';

function uuidV4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/** @return array<string, mixed> */
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
            $qs[] = rawurlencode($item['key']).'='.rawurlencode((string) $item['value']);
        }
        if ($qs !== []) {
            $result['raw'] .= '?'.implode('&', $qs);
        }
    }

    return $result;
}

/** @return list<array<string, string>> */
function headers(bool $bearer = true): array
{
    $headers = [
        ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
        ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
    ];

    if ($bearer) {
        $headers[] = ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'];
    }

    return $headers;
}

/** @return array<string, mixed> */
function loginRequest(): array
{
    return [
        'name' => 'Employee login',
        'request' => [
            'method' => 'POST',
            'header' => headers(false),
            'url' => url('/employees/login'),
            'body' => [
                'mode' => 'raw',
                'raw' => json_encode([
                    'email' => 'admin@example.com',
                    'password' => 'password',
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'options' => ['raw' => ['language' => 'json']],
            ],
            'description' => 'Login and save employee_token for the other requests.',
        ],
        'event' => [
            [
                'listen' => 'test',
                'script' => [
                    'type' => 'text/javascript',
                    'exec' => [
                        'const json = pm.response.json();',
                        'const token = json?.data?.token ?? json?.token;',
                        'if (token) {',
                        '  pm.collectionVariables.set("employee_token", token);',
                        '  pm.environment.set("employee_token", token);',
                        '}',
                    ],
                ],
            ],
        ],
    ];
}

/**
 * @param  array<string, mixed>|null  $body
 * @param  list<array<string, mixed>>  $query
 * @return array<string, mixed>
 */
function requestItem(string $name, string $method, string $path, string $description, ?array $body = null, array $query = [], ?array $testScript = null): array
{
    $request = [
        'method' => $method,
        'header' => headers(),
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
        $item['event'] = [
            [
                'listen' => 'test',
                'script' => [
                    'type' => 'text/javascript',
                    'exec' => $testScript,
                ],
            ],
        ];
    }

    return $item;
}

$instrumentTypes = [
    'electronic',
    'old_handwritten',
    'strong_argument',
    'electronic_tax_register',
    'property_ownership_owner_are_deceased_endowment',
    'property_ownership_owner_is_endowment',
    'sale_agreement',
    'electronic_deed_from_the_ministry_of_justice',
    'economic_cities_authority_suspended',
    'sublease_agreement',
    'lease_renewal',
    'property_ownership_owner_are_suspended',
    'property_ownership_owner_are_deceased',
];

$createBody = [
    'instrument_type' => 'electronic',
    'popup_status_contract' => true,
    'popup_status_realestate' => false,
    'content_popup' => 'نص محتوى البوب أب هنا',
    'button_text' => 'ابدأ الآن',
    'button_link' => 'https://example.com/start',
];

$updateBody = [
    'instrument_type' => 'lease_renewal',
    'popup_status_contract' => false,
    'popup_status_realestate' => true,
    'content_popup' => 'محتوى محدث للبوب أب',
    'button_text' => 'متابعة',
    'button_link' => 'https://example.com/continue',
];

$collection = [
    'info' => [
        '_postman_id' => uuidV4(),
        'name' => 'AQDI Admin Popup Contracts API',
        'description' => "Admin CRUD for popup-contracts.\n\nFields:\n- instrument_type (enum)\n- popup_status_contract (boolean)\n- popup_status_realestate (boolean)\n- content_popup\n- button_text\n- button_link\n\nAllowed instrument_type values:\n- ".implode("\n- ", $instrumentTypes),
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'popup_contract_id', 'value' => '1'],
    ],
    'item' => [
        [
            'name' => 'Auth',
            'item' => [
                loginRequest(),
            ],
        ],
        [
            'name' => 'Popup Contracts',
            'item' => [
                requestItem(
                    'List popup contracts',
                    'GET',
                    '/popup-contracts',
                    'List with optional filters and pagination.',
                    null,
                    [
                        ['key' => 'instrument_type', 'value' => 'electronic', 'disabled' => true],
                        ['key' => 'popup_status_contract', 'value' => '1', 'disabled' => true],
                        ['key' => 'popup_status_realestate', 'value' => '0', 'disabled' => true],
                        ['key' => 'per_page', 'value' => '20', 'disabled' => false],
                    ]
                ),
                requestItem(
                    'Create popup contract',
                    'POST',
                    '/popup-contracts',
                    'Create a popup config for an instrument type.',
                    $createBody,
                    [],
                    [
                        'const json = pm.response.json();',
                        'const id = json?.data?.id;',
                        'if (id) {',
                        '  pm.collectionVariables.set("popup_contract_id", String(id));',
                        '  pm.environment.set("popup_contract_id", String(id));',
                        '}',
                    ]
                ),
                requestItem(
                    'Show popup contract',
                    'GET',
                    '/popup-contracts/{{popup_contract_id}}',
                    'Get one popup contract by id.'
                ),
                requestItem(
                    'Update popup contract',
                    'POST',
                    '/popup-contracts/{{popup_contract_id}}',
                    'Update popup contract fields (POST update convention).',
                    $updateBody
                ),
                requestItem(
                    'Delete popup contract',
                    'POST',
                    '/popup-contracts/{{popup_contract_id}}/delete',
                    'Delete popup contract by id.'
                ),
            ],
        ],
    ],
];

file_put_contents(
    $output,
    json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
);

echo "Wrote {$output}\n";
