<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Rental-Doc-Fees.postman_collection.json
 *
 * رسوم توثيق عقد الإيجار (DocFee) — معاينة بدون حفظ + حفظ المدة على العقد.
 *
 * Run: php tools/generate_rental_doc_fees_postman_collection.php
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

/**
 * @param  array<string, mixed>  $opts
 * @return array<string, mixed>
 */
function req(string $name, string $method, string $path, array $opts = []): array
{
    $query = $opts['query'] ?? [];
    $body = $opts['body'] ?? null;
    $method = strtoupper($method);
    $public = (bool) ($opts['public'] ?? false);

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

$saveTokenScript = [
    [
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                'const json = pm.response.json();',
                'const token = json?.data?.token || json?.token;',
                'if (token) {',
                '    pm.collectionVariables.set("token", token);',
                '}',
            ],
        ],
    ],
];

$saveContractScript = [
    [
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                'const json = pm.response.json();',
                'const id = json?.data?.contract_id || json?.data?.id;',
                'if (id) { pm.collectionVariables.set("contract_id", String(id)); }',
                'const uuid = json?.data?.uuid;',
                'if (uuid) { pm.collectionVariables.set("contract_uuid", String(uuid)); }',
            ],
        ],
    ],
];

$pricingNote = <<<'TXT'
قاعدة الرسوم (أي جزء من سنة = سنة كاملة):

سكني: السنة الأولى 249 ر.س — كل سنة إضافية 150 ر.س
تجاري: السنة الأولى 349 ر.س — كل سنة إضافية 500 ر.س

أمثلة:
- سكني 1 سنة و 0 شهر → 249
- سكني 1 سنة و 1 شهر → سنتان فوترة → 399
- تجاري 2 سنة → 849

POST /api/v2/contract/doc-fee يعاين المبلغ بدون حفظ.
يجب إرسال duration_preset=other مع duration_years و duration_months + contract_type أو id.
TXT;

$folders = [
    '01 — تسجيل الدخول' => [
        req('Login', 'POST', '/auth/login', [
            'public' => true,
            'body' => [
                'mobile' => '0512345678',
                'password' => 'password123',
            ],
            'description' => 'احفظ التوكن تلقائياً في متغير collection: token',
            'event' => $saveTokenScript,
        ]),
    ],
    '02 — معاينة رسوم الإيجار (بدون حفظ)' => [
        req('سكني — سنة واحدة (249 ر.س)', 'POST', '/contract/doc-fee', [
            'body' => [
                'contract_type' => 'housing',
                'duration_preset' => 'other',
                'duration_years' => 1,
                'duration_months' => 0,
            ],
            'description' => $pricingNote."\n\nمتوقع: doc_fee = 249",
        ]),
        req('سكني — سنة + شهر (399 ر.س)', 'POST', '/contract/doc-fee', [
            'body' => [
                'contract_type' => 'housing',
                'duration_preset' => 'other',
                'duration_years' => 1,
                'duration_months' => 1,
            ],
            'description' => 'شهر إضافي يُحسب سنة. متوقع: 249 + 150 = 399. has_extra_months = true',
        ]),
        req('سكني — سنتان (399 ر.س)', 'POST', '/contract/doc-fee', [
            'body' => [
                'contract_type' => 'housing',
                'duration_preset' => 'other',
                'duration_years' => 2,
                'duration_months' => 0,
            ],
            'description' => 'متوقع: 249 + 150 = 399',
        ]),
        req('تجاري — سنة واحدة (349 ر.س)', 'POST', '/contract/doc-fee', [
            'body' => [
                'contract_type' => 'commercial',
                'duration_preset' => 'other',
                'duration_years' => 1,
                'duration_months' => 0,
            ],
            'description' => 'متوقع: doc_fee = 349',
        ]),
        req('تجاري — سنتان (849 ر.س)', 'POST', '/contract/doc-fee', [
            'body' => [
                'contract_type' => 'commercial',
                'duration_preset' => 'other',
                'duration_years' => 2,
                'duration_months' => 0,
            ],
            'description' => 'متوقع: 349 + 500 = 849',
        ]),
        req('معاينة حسب عقد موجود', 'POST', '/contract/doc-fee', [
            'body' => [
                'id' => '{{contract_id}}',
                'duration_preset' => 'other',
                'duration_years' => 1,
                'duration_months' => 3,
            ],
            'description' => 'بدون contract_type: النوع يُقرأ من العقد. سكني 1سنة+3أشهر = سنتان فوترة = 399',
        ]),
    ],
    '03 — حفظ الرسوم على العقد' => [
        req('بدء عقد سكني', 'POST', '/contract/start', [
            'body' => [
                'contract_type' => 'housing',
                'instrument_type' => 'old_handwritten',
                'is_real' => false,
            ],
            'description' => 'يحفظ contract_id و uuid',
            'event' => $saveContractScript,
        ]),
        req('Step 6 — مدة أخرى (سنة + شهر)', 'POST', '/contract/step6', [
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
                'tenant_role_ids' => [1],
                'additional_terms' => false,
            ],
            'description' => 'يحفظ duration_* على العقد. الرد يتضمن doc_fee و doc_fee_lines (إجمالي الرسوم شامل رسوم إيجار).',
        ]),
        req('Step 6 — مدة جاهزة سنة (بدون مدة أخرى)', 'POST', '/contract/step6', [
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
                'tenant_role_ids' => [1],
                'additional_terms' => false,
            ],
            'description' => 'زر مدة جاهزة: يمسح duration_preset. السعر يأتي من مدة العقد القديمة إن لم تُحفظ مدة أخرى.',
        ]),
    ],
    '04 — عرض الرسوم بعد الحفظ' => [
        req('الملخص المالي للعقد', 'GET', '/financial/{{contract_uuid}}', [
            'description' => 'يعيد price_details.doc_fee و billable_years و doc_fee_lines بعد حفظ مدة أخرى.',
        ]),
        req('تفاصيل العقد', 'GET', '/contracts/{{contract_id}}'),
        req('فاتورة العقد', 'GET', '/invoices/{{contract_id}}', [
            'description' => 'Invoice.rental_fees تُشتق من رسوم التوثيق عند إنشاء الفاتورة.',
        ]),
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
];

$collection = [
    'info' => [
        '_postman_id' => uuid4(),
        'name' => 'AQDI — رسوم إيجار (توثيق العقد)',
        'description' => "كولكشن رسوم توثيق عقد الإيجار.\n\n**Base:** `{{baseUrl}}/api/v2`\n**Auth:** Bearer `{{token}}` (شغّل Login أولاً)\n\n".$pricingNote."\n\nGenerated by `php tools/generate_rental_doc_fees_postman_collection.php`",
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

$collectionPath = $postmanDir.'/AQDI-Rental-Doc-Fees.postman_collection.json';

file_put_contents(
    $collectionPath,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

$requestCount = array_sum(array_map(static fn ($f) => count($f), $folders));

echo "Wrote {$collectionPath}\n";
echo "Folders: ".count($folders).", Requests: {$requestCount}\n";
