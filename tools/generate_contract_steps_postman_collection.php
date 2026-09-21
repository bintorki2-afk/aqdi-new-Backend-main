<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Contract-Steps.postman_collection.json
 *
 * UI flow only (contract creation steps). Each request maps to the live V2 endpoint.
 *
 * Run: php tools/generate_contract_steps_postman_collection.php
 */

$basePath = dirname(__DIR__);

const STEPS_COLLECTION_ID = 'b7c1e4a0-2d5f-4a91-9c3e-8f0a1b2c3d4e';

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
 * @param  array<string, mixed>  $data
 * @return list<array<string, mixed>>
 */
function flattenForm(array $data, string $prefix = ''): array
{
    $out = [];
    foreach ($data as $key => $value) {
        $name = $prefix === '' ? (string) $key : $prefix.'['.$key.']';
        if (is_array($value) && ($value['type'] ?? null) === 'file') {
            $out[] = [
                'key' => $name,
                'type' => 'file',
                'src' => $value['src'] ?? '',
            ];
            continue;
        }
        if (is_array($value)) {
            $out = array_merge($out, flattenForm($value, $name));
            continue;
        }
        if (is_bool($value)) {
            $out[] = ['key' => $name, 'type' => 'text', 'value' => $value ? 'true' : 'false'];
            continue;
        }
        if ($value === null) {
            continue;
        }
        $out[] = ['key' => $name, 'type' => 'text', 'value' => (string) $value];
    }

    return $out;
}

/**
 * @param  array<string, mixed>  $opts
 * @return array<string, mixed>
 */
function req(string $name, string $method, string $path, array $opts = []): array
{
    $method = strtoupper($method);
    $body = $opts['body'] ?? null;
    $form = $opts['form'] ?? null;

    $request = [
        'method' => $method,
        'header' => hdr($body !== null && $form === null),
        'url' => buildUrl($path, $opts['query'] ?? []),
    ];

    if ($form !== null) {
        $request['body'] = [
            'mode' => 'formdata',
            'formdata' => flattenForm($form),
        ];
        $request['header'] = hdr(false);
    } elseif ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'options' => ['raw' => ['language' => 'json']],
        ];
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

$file = static fn (): array => ['type' => 'file', 'src' => ''];

$saveIdsScript = [
    [
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                'const json = pm.response.json();',
                'const id = json?.data?.contract_id || json?.data?.id;',
                'if (id) { pm.collectionVariables.set("contract_id", String(id)); }',
                'const uuid = json?.data?.uuid || json?.data?.contract?.uuid;',
                'if (uuid) { pm.collectionVariables.set("contract_uuid", uuid); }',
            ],
        ],
    ],
];

$mapsUrl = 'https://maps.google.com/?q=24.7136,46.6753';

$firstStepBase = [
    'id' => '{{contract_id}}',
    'address_url' => $mapsUrl,
];

$items = [
    folder('1. Start', [
        req('Start Housing Contract', 'POST', '/contract/start', [
            'body' => [
                'contract_type' => 'housing',
                'instrument_type' => 'electronic',
                'is_real' => false,
            ],
            'description' => 'Creates the contract. Saves `contract_id` / `contract_uuid`. Then run First Step.',
            'event' => $saveIdsScript,
        ]),
        req('Start Commercial Contract', 'POST', '/contract/start', [
            'body' => [
                'contract_type' => 'commercial',
                'instrument_type' => 'electronic',
                'is_real' => false,
            ],
            'event' => $saveIdsScript,
        ]),
        req('Start from Saved Property', 'POST', '/contract/start', [
            'body' => [
                'contract_type' => 'housing',
                'instrument_type' => 'electronic',
                'is_real' => true,
                'real_id' => '{{real_estate_id}}',
                'unit_ids' => [1],
            ],
            'event' => $saveIdsScript,
        ]),
        req('Check Uncompleted Contract', 'GET', '/contract/check-uncompleted-contract', [
            'query' => ['contract_type' => 'housing'],
            'description' => 'Required `contract_type=housing|commercial`. Only an incomplete contract of the same type returns check=true.',
        ]),
        req('Get Current Contract Step', 'POST', '/contract/uncompleted-contract', [
            'body' => ['uuid' => '{{contract_uuid}}'],
        ]),
    ], 'POST /contract/start — create the contract before any step.'),

    folder('2. First Step — Deed and Address', [
        req('Electronic Deed — Upload Image', 'POST', '/contract/step1', [
            'form' => array_merge($firstStepBase, [
                'instrument_type' => 'electronic',
                'image_instrument' => $file(),
            ]),
            'description' => "UI: صك ملكية إلكتروني + رفع صورة الصك + رابط قوقل ماب.\n`instrument_type=electronic` (UI alias: electronic_deed_from_the_ministry_of_justice).\nFile: png / jpeg / pdf.",
        ]),
        req('Electronic Deed — Manual Number and Date', 'POST', '/contract/step1', [
            'body' => array_merge($firstStepBase, [
                'instrument_type' => 'electronic',
                'instrument_number' => '1234567890',
                'type_instrument_history' => 'hijri',
                'instrument_history_day' => 15,
                'instrument_history_month' => 6,
                'instrument_history_year' => 1440,
            ]),
            'description' => 'UI: إدخال بيانات الصك يدويًا بدل الصورة + رابط قوقل ماب.',
        ]),
        req('Electronic Deed — Manual Address', 'POST', '/contract/step1', [
            'body' => [
                'id' => '{{contract_id}}',
                'instrument_type' => 'electronic',
                'instrument_number' => '1234567890',
                'property_place_id' => 1,
                'property_city_id' => 1,
                'neighborhood' => 'حي النخيل',
                'street' => 'شارع الملك',
                'building_number' => '12',
                'postal_code' => '12345',
                'extra_figure' => '1234',
            ],
            'description' => 'UI: إدخال العنوان يدويًا — المدينة والحي والشارع.',
        ]),
        req('Electronic Deed — Address Card Image', 'POST', '/contract/step1', [
            'form' => [
                'id' => '{{contract_id}}',
                'instrument_type' => 'electronic',
                'image_address' => $file(),
            ],
            'description' => 'UI: إرفاق العنوان الوطني — صورة بطاقة العنوان.',
        ]),
        req('Old Handwritten Deed', 'POST', '/contract/step1', [
            'form' => array_merge($firstStepBase, [
                'instrument_type' => 'old_handwritten',
                'image_instrument_from_the_front' => $file(),
                'image_instrument_from_the_back' => $file(),
            ]),
            'description' => 'صك يدوي قديم.',
        ]),
        req('Registry Deed (Strong Argument)', 'POST', '/contract/step1', [
            'form' => array_merge($firstStepBase, [
                'instrument_type' => 'strong_argument',
                'real_estate_registry_number' => 'REG-001',
                'type_date_first_registration' => 'hijri',
                'date_first_registration_day' => 1,
                'date_first_registration_month' => 1,
                'date_first_registration_year' => 1440,
                'image_instrument' => $file(),
            ]),
            'description' => 'صك ملكية إلكتروني من السجل العقاري.',
        ]),
        req('Owner Endowment Deed', 'POST', '/contract/step1', [
            'form' => array_merge($firstStepBase, [
                'instrument_type' => 'property_ownership_owner_is_endowment',
                'image_instrument' => $file(),
                'copy_of_the_endowment_registration_certificate' => $file(),
                'copy_of_the_trusteeship_deed' => $file(),
                'is_multiple_trusteeship_deed_copy' => false,
            ]),
            'description' => 'صك ملكية ومالك العقار وقف. Optional: copy_of_guardians_power_of_attorney_for_agent when multiple trustees.',
        ]),
        req('Owner Deceased Deed', 'POST', '/contract/step1', [
            'form' => array_merge($firstStepBase, [
                'instrument_type' => 'property_ownership_owner_are_deceased',
                'Image_inheritance_certificate' => $file(),
                'copy_power_of_attorney_from_heirs_to_agent' => $file(),
            ]),
            'description' => 'صك ملكية لمالك متوفى.',
        ]),
        req('Owner Deceased Endowment Deed', 'POST', '/contract/step1', [
            'form' => array_merge($firstStepBase, [
                'instrument_type' => 'property_ownership_owner_are_deceased_endowment',
                'copy_of_the_endowment_registration_certificate' => $file(),
                'Image_inheritance_certificate' => $file(),
            ]),
            'description' => 'صك ملكية وقف لمالك متوفى.',
        ]),
        req('Sale Agreement', 'POST', '/contract/step1', [
            'form' => array_merge($firstStepBase, [
                'instrument_type' => 'sale_agreement',
                'image_instrument' => $file(),
            ]),
            'description' => 'عقد بيع.',
        ]),
        req('Electronic Tax Register', 'POST', '/contract/step1', [
            'form' => array_merge($firstStepBase, [
                'instrument_type' => 'electronic_tax_register',
                'image_instrument' => $file(),
            ]),
            'description' => 'سجل ضريبي إلكتروني.',
        ]),
        req('Owner Suspended Deed', 'POST', '/contract/step1', [
            'form' => array_merge($firstStepBase, [
                'instrument_type' => 'property_ownership_owner_are_suspended',
                'image_instrument' => $file(),
            ]),
            'description' => 'صك ملكية (مالك موقوف).',
        ]),
        req('Economic Cities Authority', 'POST', '/contract/step1', [
            'form' => array_merge($firstStepBase, [
                'instrument_type' => 'economic_cities_authority_suspended',
                'image_instrument' => $file(),
            ]),
            'description' => 'هيئة المدن الاقتصادية (معلق).',
        ]),
        req('Sublease Agreement', 'POST', '/contract/step1', [
            'body' => array_merge($firstStepBase, [
                'instrument_type' => 'sublease_agreement',
            ]),
            'description' => 'اتفاقية إعارة من الباطن. Start skips to owner/tenant (step 3). First step is optional.',
        ]),
        req('Lease Renewal', 'POST', '/contract/step1', [
            'body' => array_merge($firstStepBase, [
                'instrument_type' => 'lease_renewal',
            ]),
            'description' => 'تجديد عقد إيجار. Start skips to owner/tenant (step 3). First step is optional.',
        ]),
    ], "POST /contract/step1\n\nUI: نوع المستند + العنوان الوطني (رابط قوقل ماب / يدوي / صورة البطاقة).\nالفرق بين الطلبات هنا هو نوع العقد/الصك فقط. العنوان جزء من نفس الخطوة."),

    folder('3. Owner Details', [
        req('Complete Owner Details', 'POST', '/contract/step3', [
            'body' => [
                'id' => '{{contract_id}}',
                'property_owner_id_num' => '1234567890',
                'property_owner_mobile' => '0512345678',
                'type_dob_property_owner' => 'hijri',
                'property_owner_dob_day' => '15',
                'property_owner_dob_month' => '06',
                'property_owner_dob_year' => '1410',
                'add_legal_agent_of_owner' => false,
            ],
            'description' => 'UI: بيانات مالك العقار. POST /contract/step3',
        ]),
        req('Complete Owner Details with Agent', 'POST', '/contract/step3', [
            'form' => [
                'id' => '{{contract_id}}',
                'property_owner_id_num' => '1234567890',
                'property_owner_mobile' => '0512345678',
                'type_dob_property_owner' => 'hijri',
                'property_owner_dob_day' => '15',
                'property_owner_dob_month' => '06',
                'property_owner_dob_year' => '1410',
                'add_legal_agent_of_owner' => true,
                'id_num_of_property_owner_agent' => '1098765432',
                'mobile_of_property_owner_agent' => '0598765432',
                'type_dob_property_owner_agent' => 'gregorian',
                'dob_of_property_owner_agent_day' => '01',
                'dob_of_property_owner_agent_month' => '01',
                'dob_of_property_owner_agent_year' => '1990',
                'copy_of_the_authorization_or_agency' => $file(),
            ],
            'description' => 'UI: إضافة وكيل عن المالك + إرفاق صورة الوكالة. POST /contract/step3',
        ]),
    ], 'UI: بيانات مالك العقار. API: POST /contract/step3'),

    folder('4. Tenant Details', [
        req('Individual Tenant', 'POST', '/contract/step4', [
            'body' => [
                'id' => '{{contract_id}}',
                'tenant_entity' => 'person',
                'tenant_id_num' => '1111111111',
                'tenant_mobile' => '0511111111',
                'type_tenant_dob' => 'hijri',
                'tenant_dob_day' => '02',
                'tenant_dob_month' => '02',
                'tenant_dob_year' => '1428',
            ],
            'description' => 'UI: صفة المستأجر = فرد. POST /contract/step4',
        ]),
        req('Establishment — Registry Owner', 'POST', '/contract/step4', [
            'body' => [
                'id' => '{{contract_id}}',
                'tenant_entity' => 'institution',
                'authorization_type' => 'owner_and_representative_of_record',
                'tenant_entity_unified_registry_number' => '7000000000',
                'id_num_of_property_tenant_agent' => '1234567890',
                'mobile_of_property_tenant_agent' => '0512345678',
                'type_dob_tenant_agent' => 'hijri',
                'dobof_property_tenant_agent_day' => '01',
                'dobof_property_tenant_agent_month' => '01',
                'dobof_property_tenant_agent_year' => '1410',
            ],
            'description' => 'UI: مؤسسة أو شركة — أنا مالك السجل وممثله. السجل الموحد 10 أرقام ويبدأ بـ 7. POST /contract/step4',
        ]),
        req('Establishment — Agent', 'POST', '/contract/step4', [
            'form' => [
                'id' => '{{contract_id}}',
                'tenant_entity' => 'institution',
                'authorization_type' => 'agent_for_the_tenant',
                'tenant_entity_unified_registry_number' => '7000000000',
                'id_num_of_property_tenant_agent' => '1234567890',
                'mobile_of_property_tenant_agent' => '0512345678',
                'type_dob_tenant_agent' => 'hijri',
                'dobof_property_tenant_agent_day' => '01',
                'dobof_property_tenant_agent_month' => '01',
                'dobof_property_tenant_agent_year' => '1410',
                'copy_of_the_owner_record' => $file(),
            ],
            'description' => "UI: مؤسسة أو شركة — أنا وكيل أو مفوض عن مالك السجل.\nFile `copy_of_the_owner_record`: jpg, jpeg, png, or pdf. POST /contract/step4",
        ]),
    ], 'UI: بيانات المستأجر (فرد / مؤسسة مالك سجل / مؤسسة وكيل). API: POST /contract/step4'),

    folder('5. Unit Details', [
        req('Complete Unit Details', 'POST', '/contract/step5', [
            'body' => [
                'id' => '{{contract_id}}',
                'units' => [
                    [
                        'unit_type_id' => 1,
                        'unit_usage_id' => 1,
                        'unit_number' => '101',
                        'floor_number' => 1,
                        'unit_area' => 120,
                        'kitchen_tank' => 0,
                        'furnished' => 0,
                        'electricity_meter' => 1,
                        'water_meter' => 1,
                    ],
                ],
            ],
            'description' => 'UI: بيانات الوحدة. POST /contract/step5. Prefer `units[]`.',
        ]),
    ], 'UI: بيانات الوحدة. API: POST /contract/step5'),

    folder('6. Financial', [
        req('Preview Documentation Fee', 'POST', '/contract/doc-fee', [
            'body' => [
                'contract_type' => 'housing',
                'duration_preset' => 'other',
                'duration_years' => 1,
                'duration_months' => 0,
            ],
            'description' => 'معاينة رسوم التوثيق بدون حفظ. POST /contract/doc-fee',
        ]),
        req('Complete Financial Details', 'POST', '/contract/step6', [
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
            'description' => "UI: المالية — مدة العقد، الإيجار السنوي، طريقة الدفع.\nPOST /contract/step6. For custom duration use `duration_preset: other` with years/months.",
        ]),
        req('Save Contract Draft', 'POST', '/contract/draft', [
            'body' => [
                'id' => '{{contract_id}}',
                'is_draft' => true,
            ],
            'description' => 'حفظ وإكمال لاحقاً.',
        ]),
    ], 'UI: المالية. API: POST /contract/step6'),
];

$collection = [
    'info' => [
        '_postman_id' => STEPS_COLLECTION_ID,
        'name' => 'AQDI Contract Steps',
        'description' => <<<'MD'
خطوات إنشاء العقد فقط (API V2).

**Base:** `{{baseUrl}}/api/v2`  
**Auth:** Bearer `{{token}}`

شغّل **1. Start** أولاً حتى يُحفظ `contract_id`.

| UI | Endpoint |
|---|---|
| Start | `POST /contract/start` |
| الخطوة الأولى — نوع المستند + العنوان الوطني | `POST /contract/step1` |
| بيانات المالك | `POST /contract/step3` |
| بيانات المستأجر | `POST /contract/step4` |
| بيانات الوحدة | `POST /contract/step5` |
| المالية | `POST /contract/step6` |

الخطوة الأولى تحتوي على طلب لكل نوع صك/عقد. العنوان (قوقل ماب / يدوي / صورة البطاقة) جزء من نفس الخطوة.

رفع الملفات: الطلبات التي فيها صور تستخدم `form-data`. اختر الملف من Postman قبل الإرسال.

Generated by `php tools/generate_contract_steps_postman_collection.php`
MD,
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        '_exporter_id' => 'aqdi-blade',
    ],
    'item' => $items,
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000', 'type' => 'default'],
        ['key' => 'token', 'value' => '', 'type' => 'secret'],
        ['key' => 'contract_id', 'value' => '1', 'type' => 'default'],
        ['key' => 'contract_uuid', 'value' => '', 'type' => 'default'],
        ['key' => 'real_estate_id', 'value' => '1', 'type' => 'default'],
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

$collectionPath = $postmanDir.'/AQDI-Contract-Steps.postman_collection.json';

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
