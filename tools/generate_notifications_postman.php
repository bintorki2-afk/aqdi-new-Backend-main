<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-Notifications-API.postman_collection.json
 * Run: php tools/generate_notifications_postman.php
 */

$output = dirname(__DIR__).'/postman/AQDI-Admin-Notifications-API.postman_collection.json';

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
function requestItem(
    string $name,
    string $method,
    string $path,
    string $description,
    ?array $body = null,
    array $query = [],
    ?array $testScript = null,
    bool $bearer = true
): array {
    $request = [
        'method' => $method,
        'header' => headers($bearer),
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

$description = 'Firebase push notifications (admin). Auth: Bearer {{employee_token}}. '
    .'Audiences: user, custom_user, employee, custom_employee, all_users, all_employees.';

$collection = [
    'info' => [
        '_postman_id' => uuidV4(),
        'name' => 'AQDI Admin Notifications API',
        'description' => $description,
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'https://aqdi.sa'],
        ['key' => 'employee_token', 'value' => ''],
        ['key' => 'user_id', 'value' => '1'],
        ['key' => 'employee_id', 'value' => '1'],
        ['key' => 'fcm_token', 'value' => 'DEVICE_FCM_TOKEN'],
    ],
    'item' => [
        [
            'name' => 'Auth',
            'item' => [
                requestItem(
                    'Employee login (save token + fcm)',
                    'POST',
                    '/employees/login',
                    'Login and optionally save employee FCM token.',
                    [
                        'email' => 'mohammed@aqdi.com',
                        'password' => 'password',
                        'fcm_token' => '{{fcm_token}}',
                    ],
                    [],
                    [
                        'const json = pm.response.json();',
                        'const token = json?.data?.token;',
                        'if (token) { pm.collectionVariables.set("employee_token", token); }',
                        'const id = json?.data?.id;',
                        'if (id) { pm.collectionVariables.set("employee_id", String(id)); }',
                    ],
                    false
                ),
                requestItem(
                    'Update employee FCM token',
                    'POST',
                    '/employees/fcm',
                    'Update current employee FCM token.',
                    [
                        'fcm_token' => '{{fcm_token}}',
                    ]
                ),
            ],
        ],
        [
            'name' => 'Send notifications',
            'item' => [
                requestItem(
                    'Send to user',
                    'POST',
                    '/notifications/user',
                    'Send notification to one user by user_id.',
                    [
                        'user_id' => '{{user_id}}',
                        'title' => 'تنبيه',
                        'body' => 'رسالة للمستخدم',
                        'data' => ['screen' => 'home'],
                    ]
                ),
                requestItem(
                    'Send custom user',
                    'POST',
                    '/notifications/custom-user',
                    'Custom notification to one user.',
                    [
                        'user_id' => '{{user_id}}',
                        'title' => 'رسالة مخصصة',
                        'body' => 'محتوى مخصص للمستخدم',
                    ]
                ),
                requestItem(
                    'Send to employee',
                    'POST',
                    '/notifications/employee',
                    'Send notification to one employee by employee_id.',
                    [
                        'employee_id' => '{{employee_id}}',
                        'title' => 'تنبيه موظف',
                        'body' => 'رسالة للموظف',
                    ]
                ),
                requestItem(
                    'Send custom employee',
                    'POST',
                    '/notifications/custom-employee',
                    'Custom notification to one employee.',
                    [
                        'employee_id' => '{{employee_id}}',
                        'title' => 'رسالة مخصصة للموظف',
                        'body' => 'محتوى مخصص',
                    ]
                ),
                requestItem(
                    'Send to all users',
                    'POST',
                    '/notifications/all-users',
                    'Broadcast to all users (topic + tokens).',
                    [
                        'title' => 'إعلان',
                        'body' => 'رسالة لجميع المستخدمين',
                    ]
                ),
                requestItem(
                    'Send to all employees',
                    'POST',
                    '/notifications/all-employees',
                    'Broadcast to all employees (topic + tokens).',
                    [
                        'title' => 'إعلان موظفين',
                        'body' => 'رسالة لجميع الموظفين',
                    ]
                ),
                requestItem(
                    'Generic send (audience)',
                    'POST',
                    '/notifications/send',
                    'Unified endpoint. audience: user | custom_user | employee | custom_employee | all_users | all_employees',
                    [
                        'audience' => 'all_employees',
                        'title' => 'إعلان',
                        'body' => 'رسالة عبر audience',
                        'data' => ['type' => 'broadcast'],
                    ]
                ),
            ],
        ],
    ],
];

if (! is_dir(dirname($output))) {
    mkdir(dirname($output), 0777, true);
}

file_put_contents($output, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
echo "Wrote {$output}\n";
