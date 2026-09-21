<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-Popup-And-Payment-Messages-API.postman_collection.json
 * Run: php tools/generate_popup_and_payment_messages_postman.php
 */

$output = dirname(__DIR__).'/postman/AQDI-Admin-Popup-And-Payment-Messages-API.postman_collection.json';

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
            'description' => 'Login and save employee_token.',
        ],
        'event' => [[
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
        ]],
    ];
}

/**
 * @param  array<string, mixed>|null  $body
 * @param  list<array<string, mixed>>  $query
 * @param  list<string>|null  $testScript
 * @return array<string, mixed>
 */
function requestItem(
    string $name,
    string $method,
    string $path,
    string $description,
    ?array $body = null,
    array $query = [],
    ?array $testScript = null
): array {
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

$saveIdScript = static function (string $var): array {
    return [
        'const json = pm.response.json();',
        'const id = json?.data?.id;',
        'if (id) {',
        "  pm.collectionVariables.set(\"{$var}\", String(id));",
        "  pm.environment.set(\"{$var}\", String(id));",
        '}',
    ];
};

$collection = [
    'info' => [
        '_postman_id' => uuidV4(),
        'name' => 'AQDI Admin Popup Contracts & Payment Messages',
        'description' => "Admin APIs:\n1) Popup Contracts `/api/admin/popup-contracts`\n2) Payment Messages `/api/admin/payment-messages`\n\nPopup instrument_type values:\n- ".implode("\n- ", $instrumentTypes)."\n\nPayment message type: success | failed",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'popup_contract_id', 'value' => '1'],
        ['key' => 'payment_message_id', 'value' => '1'],
    ],
    'item' => [
        [
            'name' => 'Auth',
            'item' => [loginRequest()],
        ],
        [
            'name' => 'Popup Contracts',
            'item' => [
                requestItem('List popup contracts', 'GET', '/popup-contracts', 'List popup configs.', null, [
                    ['key' => 'instrument_type', 'value' => 'electronic', 'disabled' => true],
                    ['key' => 'popup_status_contract', 'value' => '1', 'disabled' => true],
                    ['key' => 'popup_status_realestate', 'value' => '0', 'disabled' => true],
                    ['key' => 'per_page', 'value' => '20'],
                ]),
                requestItem('Create popup contract', 'POST', '/popup-contracts', 'Create popup for instrument type.', [
                    'instrument_type' => 'electronic',
                    'popup_status_contract' => true,
                    'popup_status_realestate' => false,
                    'content_popup' => 'نص محتوى البوب أب',
                    'button_text' => 'ابدأ الآن',
                    'button_link' => 'https://example.com/start',
                ], [], $saveIdScript('popup_contract_id')),
                requestItem('Show popup contract', 'GET', '/popup-contracts/{{popup_contract_id}}', 'Get one popup contract.'),
                requestItem('Update popup contract', 'POST', '/popup-contracts/{{popup_contract_id}}', 'Update popup contract.', [
                    'instrument_type' => 'lease_renewal',
                    'popup_status_contract' => false,
                    'popup_status_realestate' => true,
                    'content_popup' => 'محتوى محدث',
                    'button_text' => 'متابعة',
                    'button_link' => 'https://example.com/continue',
                ]),
                requestItem('Delete popup contract', 'POST', '/popup-contracts/{{popup_contract_id}}/delete', 'Delete popup contract.'),
            ],
        ],
        [
            'name' => 'Payment Messages',
            'item' => [
                requestItem('List payment messages', 'GET', '/payment-messages', 'List success/failed payment messages.', null, [
                    ['key' => 'type', 'value' => 'success', 'disabled' => true],
                    ['key' => 'per_page', 'value' => '20'],
                ]),
                requestItem('Create success message', 'POST', '/payment-messages', 'Create success payment message.', [
                    'type' => 'success',
                    'message' => 'تم الدفع بنجاح',
                    'button_text' => 'عرض العقد',
                    'button_link' => 'https://example.com/contracts',
                    'button_text_2' => 'الصفحة الرئيسية',
                    'button_link_2' => 'https://example.com',
                ], [], $saveIdScript('payment_message_id')),
                requestItem('Create failed message', 'POST', '/payment-messages', 'Create failed payment message.', [
                    'type' => 'failed',
                    'message' => 'فشل الدفع، حاول مرة أخرى',
                    'button_text' => 'إعادة المحاولة',
                    'button_link' => 'https://example.com/payment/retry',
                    'button_text_2' => 'الدعم',
                    'button_link_2' => 'https://example.com/support',
                ]),
                requestItem('Show payment message', 'GET', '/payment-messages/{{payment_message_id}}', 'Get one payment message.'),
                requestItem('Update payment message', 'POST', '/payment-messages/{{payment_message_id}}', 'Update payment message.', [
                    'message' => 'رسالة محدثة',
                    'button_text' => 'متابعة',
                    'button_link' => 'https://example.com/continue',
                    'button_text_2' => 'إلغاء',
                    'button_link_2' => 'https://example.com/cancel',
                ]),
                requestItem('Delete payment message', 'POST', '/payment-messages/{{payment_message_id}}/delete', 'Delete payment message.'),
            ],
        ],
    ],
];

file_put_contents(
    $output,
    json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
);

echo "Wrote {$output}\n";
