<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-Message-Alerts-API.postman_collection.json
 * Run: php tools/generate_message_alerts_postman.php
 */

$output = dirname(__DIR__).'/postman/AQDI-Admin-Message-Alerts-API.postman_collection.json';

function uuidV4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * @param  array<string, string|null>  $query
 * @return array<string, mixed>
 */
function url(string $adminPath, array $query = []): array
{
    $adminPath = '/'.ltrim($adminPath, '/');
    $pathSegments = array_values(array_filter(explode('/', trim($adminPath, '/'))));

    $raw = '{{baseUrl}}/api/admin'.$adminPath;

    $url = [
        'raw' => $raw,
        'host' => ['{{baseUrl}}'],
        'path' => array_merge(['api', 'admin'], $pathSegments),
    ];

    if ($query !== []) {
        $url['query'] = [];
        foreach ($query as $key => $value) {
            $url['query'][] = [
                'key' => (string) $key,
                'value' => $value === null ? '' : (string) $value,
                'disabled' => $value === null || $value === '',
            ];
        }
    }

    return $url;
}

/**
 * @param  array<string, mixed>|null  $body
 * @param  array<string, string|null>  $query
 */
function request(
    string $name,
    string $method,
    string $adminPath,
    array $query = [],
    ?array $body = null,
    bool $bearer = true
): array {
    $method = strtoupper($method);
    $headers = [
        ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
    ];

    if ($bearer) {
        $headers[] = ['key' => 'Authorization', 'value' => 'Bearer {{employee_token}}', 'type' => 'text'];
    }

    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        $headers[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
    }

    $req = [
        'name' => $name,
        'request' => [
            'method' => $method,
            'header' => $headers,
            'url' => url($adminPath, $query),
        ],
    ];

    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        $req['request']['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    return $req;
}

/** @param list<array<string, mixed>> $items */
function folder(string $name, array $items, ?string $description = null): array
{
    $f = ['name' => $name, 'item' => $items];
    if ($description !== null) {
        $f['description'] = $description;
    }

    return $f;
}

/** @return list<array<string, mixed>> */
function audienceMessageItems(string $audience, string $labelAr): array
{
    $base = '/message-alerts/'.$audience;

    return [
        request("{$labelAr} — نموذج إضافة رسالة (create)", 'GET', "{$base}/create"),
        request("{$labelAr} — قائمة الرسائل", 'GET', $base, [
            'message_alert_section_id' => '{{message_alert_section_id}}',
            'message_alert_section_item_id' => '{{message_alert_section_item_id}}',
            'search' => '',
            'per_page' => '20',
        ]),
        request("{$labelAr} — إنشاء رسالة", 'POST', $base, [], [
            'message_alert_section_id' => 1,
            'message_alert_section_item_id' => 1,
            'message' => 'نص الرسالة التوضيحية',
        ]),
        request("{$labelAr} — عرض رسالة", 'GET', "{$base}/{{message_alert_id}}"),
        request("{$labelAr} — تحديث رسالة", 'POST', "{$base}/{{message_alert_id}}", [], [
            'message' => 'نص محدّث',
        ]),
        request("{$labelAr} — حذف رسالة", 'POST', "{$base}/{{message_alert_id}}/delete"),
        request("{$labelAr} — أقسام (dropdown)", 'GET', '/message-alert-sections/'.$audience.'/options/list'),
        request("{$labelAr} — بنود القسم (dropdown)", 'GET', '/message-alert-section-items/'.$audience.'/options/list', [
            'message_alert_section_id' => '{{message_alert_section_id}}',
        ]),
    ];
}

$collection = [
    'info' => [
        '_postman_id' => uuidV4(),
        'name' => 'AQDI Admin — Message Alerts API',
        'description' => "رسائل توضيحية (عميل / عقار / موظف).\n\n**المصادقة:** Bearer `{{employee_token}}` بعد `POST /api/admin/employees/login`\n\n**الجمهور (audience):** `client` | `property` | `employee`\n\n**أسماء بديلة في JSON:** `section_id`, `section_item_id`, `text`\n\n**تصدير كامل:** `GET /message-alerts/all`",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000', 'type' => 'string'],
        ['key' => 'employee_token', 'value' => '', 'type' => 'string'],
        ['key' => 'message_alert_section_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'message_alert_section_item_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'message_alert_id', 'value' => '1', 'type' => 'string'],
    ],
    'item' => [
        folder('Auth', [
            request('Employee login', 'POST', '/employees/login', [], [
                'email' => 'admin@example.com',
                'password' => 'password',
            ], false),
        ], 'احصل على token وضعه في employee_token'),
        folder('عام — Export & Types', [
            request('Types overview (بطاقات لوحة التحكم)', 'GET', '/message-alerts/types'),
            request('All messages — كل الأنواع', 'GET', '/message-alerts/all'),
            request('All messages — client فقط', 'GET', '/message-alerts/all', ['type' => 'client']),
            request('All messages — property فقط', 'GET', '/message-alerts/all', ['type' => 'property']),
            request('All messages — employee فقط', 'GET', '/message-alerts/all', ['type' => 'employee']),
        ]),
        folder('الأقسام — Sections', [
            request('List sections', 'GET', '/message-alert-sections', ['type' => 'client', 'search' => '', 'per_page' => '20']),
            request('Section options (client)', 'GET', '/message-alert-sections/client/options/list'),
            request('Section options (property)', 'GET', '/message-alert-sections/property/options/list'),
            request('Section options (employee)', 'GET', '/message-alert-sections/employee/options/list'),
            request('Create section', 'POST', '/message-alert-sections', [], [
                'name_ar' => 'قسم جديد',
                'name_en' => 'New section',
                'sort_order' => 0,
                'type' => 'client',
            ]),
            request('Show section', 'GET', '/message-alert-sections/{{message_alert_section_id}}'),
            request('Update section', 'POST', '/message-alert-sections/{{message_alert_section_id}}', [], [
                'name_ar' => 'قسم محدّث',
            ]),
            request('Delete section', 'POST', '/message-alert-sections/{{message_alert_section_id}}/delete'),
            request('Items under section — list', 'GET', '/message-alert-sections/{{message_alert_section_id}}/items', [
                'type' => 'client',
                'per_page' => '20',
            ]),
            request('Items under section — create', 'POST', '/message-alert-sections/{{message_alert_section_id}}/items', [], [
                'name_ar' => 'بند جديد',
                'name_en' => 'New item',
                'sort_order' => 0,
            ]),
        ]),
        folder('بنود الأقسام — Section items', [
            request('List items', 'GET', '/message-alert-section-items', [
                'type' => 'client',
                'message_alert_section_id' => '{{message_alert_section_id}}',
                'per_page' => '20',
            ]),
            request('Create item', 'POST', '/message-alert-section-items', [], [
                'message_alert_section_id' => 1,
                'name_ar' => 'بند القسم',
                'name_en' => 'Section item',
                'sort_order' => 0,
            ]),
            request('Show item', 'GET', '/message-alert-section-items/{{message_alert_section_item_id}}'),
            request('Update item', 'POST', '/message-alert-section-items/{{message_alert_section_item_id}}', [], [
                'name_ar' => 'بند محدّث',
            ]),
            request('Delete item', 'POST', '/message-alert-section-items/{{message_alert_section_item_id}}/delete'),
        ]),
        folder('رسائل العميل — client', audienceMessageItems('client', 'عميل')),
        folder('رسائل العقار — property', audienceMessageItems('property', 'عقار')),
        folder('رسائل الموظف — employee', audienceMessageItems('employee', 'موظف')),
        folder('Legacy (بدون audience في المسار)', [
            request('List (query type)', 'GET', '/message-alerts', ['type' => 'client', 'per_page' => '20']),
            request('Create (query type)', 'POST', '/message-alerts', [], [
                'message_alert_section_id' => 1,
                'message_alert_section_item_id' => 1,
                'message' => 'رسالة',
            ]),
            request('Show by id', 'GET', '/message-alerts/{{message_alert_id}}'),
            request('Update by id', 'POST', '/message-alerts/{{message_alert_id}}', [], ['message' => 'محدّث']),
            request('Delete by id', 'POST', '/message-alerts/{{message_alert_id}}/delete'),
        ], 'استخدم مسارات client|property|employee إن أمكن'),
    ],
];

$json = json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
file_put_contents($output, $json.PHP_EOL);

echo "Written: {$output}\n";
