<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-Payment-Messages-API.postman_collection.json
 * Run: php tools/generate_payment_messages_postman.php
 */

$output = dirname(__DIR__).'/postman/AQDI-Admin-Payment-Messages-API.postman_collection.json';

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

$collection = [
    'info' => [
        '_postman_id' => uuidV4(),
        'name' => 'AQDI Admin Payment Messages API',
        'description' => "Admin CRUD for payment success/failed messages.\n\nFields:\n- type: success | failed (unique)\n- message\n- button_text / button_link\n- button_text_2 / button_link_2",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'payment_message_id', 'value' => '1'],
    ],
    'item' => [
        [
            'name' => 'Payment Messages',
            'item' => [
                requestItem('List payment messages', 'GET', '/payment-messages', 'List success/failed message configs.', null, [
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
                ], [], [
                    'const json = pm.response.json();',
                    'const id = json?.data?.id;',
                    'if (id) { pm.collectionVariables.set("payment_message_id", String(id)); }',
                ]),
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

file_put_contents($output, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
echo "Wrote {$output}\n";
