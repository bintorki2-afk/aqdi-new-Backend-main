<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Real-Estate.postman_collection.json
 *
 * UI flow for saved properties (عقاراتي) + units CRUD. Each request maps to the live V2 endpoint.
 *
 * Run: php tools/generate_realestate_postman_collection.php
 */

$basePath = dirname(__DIR__);

const REAL_ESTATE_COLLECTION_ID = 'c8d2f5b1-3e60-4b82-ad4f-9a1b2c3d4e5f';

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
    $public = (bool) ($opts['public'] ?? false);

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

$file = static fn (): array => ['type' => 'file', 'src' => ''];

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

$saveRealEstateIdScript = [
    [
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                'const json = pm.response.json();',
                'const realId = json?.data?.real_estate?.id || json?.data?.id;',
                'if (realId) { pm.collectionVariables.set("real_estate_id", String(realId)); }',
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

$mapsUrl = 'https://maps.google.com/?q=24.7136,46.6753';
$contractTypeQuery = ['contract_type' => 'housing'];

$step1Base = [
    'contract_type' => 'housing',
    'contract_ownership' => 'owner',
    'property_type_id' => 1,
    'property_usages_id' => 1,
    'number_of_floors' => 2,
    'number_of_units_in_realestate' => '4',
];

$manualAddress = [
    'property_place_id' => 1,
    'property_city_id' => 1,
    'neighborhood' => 'حي النخيل',
    'street' => 'شارع الملك',
    'building_number' => '12',
    'postal_code' => '12345',
    'extra_figure' => '1234',
    'latitude' => 24.7136,
    'longitude' => 46.6753,
];

$unitItem = [
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
    'split_ac' => 1,
    'kitchen_tank' => 0,
    'furnished' => 0,
    'electricity_meter' => 1,
    'water_meter' => 1,
];

$unitCreateItem = array_merge($unitItem, [
    'contract_type' => 'housing',
]);

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

    folder('1. Properties', [
        req('Get Properties', 'GET', '/realstate/index', [
            'description' => 'Canonical prefix is `/realstate`. `/realState` is an alias.',
        ]),
        req('Get All Properties', 'GET', '/realstate/all'),
        req('Get Property', 'GET', '/realstate/show/{{real_estate_id}}'),
        req('Get Property Units', 'GET', '/realstate/units/{{real_estate_id}}'),
        req('Delete Property', 'DELETE', '/realstate/delete/{{real_estate_id}}', [
            'description' => 'Deletes the property and its units. Only the owner can delete.',
        ]),
    ], 'List, show, and delete saved properties. Create via Step 1.'),

    folder('2. Step 1 — Deed and Address', [
        req('Electronic Deed — Upload Image', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'electronic',
                'address_url' => $mapsUrl,
                'image_instrument' => $file(),
            ]),
            'description' => "Creates the property. Saves `real_estate_id`.\nUI: صك ملكية إلكتروني + رفع صورة الصك + رابط قوقل ماب.\n`image_instrument` is required for electronic only when deed data is not entered manually. File: png / jpeg / pdf.",
            'event' => $saveRealEstateIdScript,
        ]),
        req('Electronic Deed — Manual Number and Date', 'POST', '/realstate/step1', [
            'body' => array_merge($step1Base, [
                'instrument_type' => 'electronic',
                'instrument_number' => '1234567890',
                'type_instrument_history' => 'hijri',
                'instrument_history_day' => 15,
                'instrument_history_month' => 6,
                'instrument_history_year' => 1440,
                'address_url' => $mapsUrl,
            ]),
            'description' => 'UI: إدخال بيانات الصك يدويًا بدل الصورة + رابط قوقل ماب. الصورة غير مطلوبة.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Electronic Deed — Manual Address', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, $manualAddress, [
                'instrument_type' => 'electronic',
                'image_instrument' => $file(),
            ]),
            'description' => 'UI: إدخال العنوان يدويًا — المدينة والحي والشارع.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Electronic Deed — Address Card Image', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'electronic',
                'image_instrument' => $file(),
                'image_address' => $file(),
            ]),
            'description' => 'UI: إرفاق العنوان الوطني — صورة بطاقة العنوان.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Commercial Property', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, $manualAddress, [
                'contract_type' => 'commercial',
                'instrument_type' => 'electronic',
                'image_instrument' => $file(),
            ]),
            'description' => 'نفس الخطوة الأولى بنوع عقد تجاري.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Old Handwritten Deed', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'old_handwritten',
                'address_url' => $mapsUrl,
                'image_instrument_from_the_front' => $file(),
                'image_instrument_from_the_back' => $file(),
            ]),
            'description' => 'صك يدوي قديم.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Registry Deed (Strong Argument)', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'strong_argument',
                'real_estate_registry_number' => 'REG-001',
                'type_date_first_registration' => 'hijri',
                'date_first_registration_day' => 1,
                'date_first_registration_month' => 1,
                'date_first_registration_year' => 1440,
                'address_url' => $mapsUrl,
                'image_instrument' => $file(),
            ]),
            'description' => 'صك ملكية إلكتروني من السجل العقاري.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Owner Endowment Deed', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'property_ownership_owner_is_endowment',
                'address_url' => $mapsUrl,
                'image_instrument' => $file(),
                'copy_of_the_endowment_registration_certificate' => $file(),
                'copy_of_the_trusteeship_deed' => $file(),
                'is_multiple_trusteeship_deed_copy' => false,
            ]),
            'description' => 'صك ملكية ومالك العقار وقف. `image_instrument` + شهادات الوقف مطلوبة. Optional: copy_of_guardians_power_of_attorney_for_agent when multiple trustees.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Owner Deceased Deed', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'property_ownership_owner_are_deceased',
                'address_url' => $mapsUrl,
                'Image_inheritance_certificate' => $file(),
                'copy_power_of_attorney_from_heirs_to_agent' => $file(),
            ]),
            'description' => 'صك ملكية لمالك متوفى.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Owner Deceased Endowment Deed', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'property_ownership_owner_are_deceased_endowment',
                'address_url' => $mapsUrl,
                'copy_of_the_endowment_registration_certificate' => $file(),
                'Image_inheritance_certificate' => $file(),
            ]),
            'description' => 'صك ملكية وقف لمالك متوفى.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Sale Agreement', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'sale_agreement',
                'address_url' => $mapsUrl,
                'image_instrument' => $file(),
            ]),
            'description' => 'عقد بيع.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Electronic Tax Register', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'electronic_tax_register',
                'address_url' => $mapsUrl,
                'image_instrument' => $file(),
            ]),
            'description' => 'سجل ضريبي إلكتروني.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Owner Suspended Deed', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'property_ownership_owner_are_suspended',
                'address_url' => $mapsUrl,
                'image_instrument' => $file(),
            ]),
            'description' => 'صك ملكية (مالك موقوف).',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Economic Cities Authority', 'POST', '/realstate/step1', [
            'form' => array_merge($step1Base, [
                'instrument_type' => 'economic_cities_authority_suspended',
                'address_url' => $mapsUrl,
                'image_instrument' => $file(),
            ]),
            'description' => 'هيئة المدن الاقتصادية (معلق).',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Sublease Agreement', 'POST', '/realstate/step1', [
            'body' => array_merge($step1Base, $manualAddress, [
                'instrument_type' => 'sublease_agreement',
            ]),
            'description' => 'اتفاقية إعارة من الباطن. لا يتطلب صورة صك.',
            'event' => $saveRealEstateIdScript,
        ]),
        req('Lease Renewal', 'POST', '/realstate/step1', [
            'body' => array_merge($step1Base, $manualAddress, [
                'instrument_type' => 'lease_renewal',
            ]),
            'description' => 'تجديد عقد إيجار. لا يتطلب صورة صك.',
            'event' => $saveRealEstateIdScript,
        ]),
    ], "POST /realstate/step1 — creates the property.\n\nUI: نوع المستند + العنوان الوطني (رابط قوقل ماب / يدوي / صورة البطاقة).\n`image_instrument` is required for electronic only when the deed is not entered manually, and always for owner-endowment.\nChoose the file in Postman before sending form-data requests."),

    folder('3. Step 2 — Owner Details', [
        req('Complete Owner Details', 'POST', '/realstate/step2', [
            'body' => [
                'id' => '{{real_estate_id}}',
                'name_real_estate' => 'عقاري المحفوظ',
                'type_dob_property_owner' => 'hijri',
                'name_owner' => 'اسم المالك',
                'property_owner_id_num' => '1234567890',
                'property_owner_dob_day' => '15',
                'property_owner_dob_month' => '06',
                'property_owner_dob_year' => '1410',
                'property_owner_mobile' => '0512345678',
                'property_owner_iban' => 'SA0380000000608010167500',
                'add_legal_agent_of_owner' => false,
            ],
            'description' => 'UI: بيانات مالك العقار. POST /realstate/step2. `property_owner_id_num` and `property_owner_mobile` are required.',
        ]),
        req('Complete Owner Details with Agent', 'POST', '/realstate/step2', [
            'form' => [
                'id' => '{{real_estate_id}}',
                'name_real_estate' => 'عقاري المحفوظ',
                'type_dob_property_owner' => 'hijri',
                'name_owner' => 'اسم المالك',
                'property_owner_id_num' => '1234567890',
                'property_owner_dob_day' => '15',
                'property_owner_dob_month' => '06',
                'property_owner_dob_year' => '1410',
                'property_owner_mobile' => '0512345678',
                'property_owner_iban' => 'SA0380000000608010167500',
                'add_legal_agent_of_owner' => true,
                'id_num_of_property_owner_agent' => '1098765432',
                'mobile_of_property_owner_agent' => '0598765432',
                'type_dob_property_owner_agent' => 'gregorian',
                'dob_of_property_owner_agent_day' => '01',
                'dob_of_property_owner_agent_month' => '01',
                'dob_of_property_owner_agent_year' => '1990',
                'copy_of_the_authorization_or_agency' => $file(),
            ],
            'description' => 'UI: إضافة وكيل عن المالك + إرفاق صورة الوكالة. POST /realstate/step2',
        ]),
    ], 'UI: بيانات مالك العقار. API: POST /realstate/step2'),

    folder('4. Step 3 — Units', [
        req('Complete Property Units', 'POST', '/realstate/step3', [
            'body' => [
                'id' => '{{real_estate_id}}',
                'units' => [
                    $unitItem,
                    array_merge($unitItem, [
                        'unit_number' => '102',
                        'unit_area' => 90,
                        'tootal_rooms' => 2,
                    ]),
                ],
            ],
            'description' => 'UI: بيانات الوحدات. POST /realstate/step3. Prefer `units[]`. A legacy flat payload is still accepted.',
        ]),
        req('Finish Property without Units', 'POST', '/realstate/step3', [
            'body' => [
                'id' => '{{real_estate_id}}',
            ],
            'description' => '`units` may be omitted to finish the property without attaching units yet. Add units later via Units CRUD.',
        ]),
        req('Attach Existing Units by ID', 'POST', '/realstate/step3', [
            'body' => [
                'id' => '{{real_estate_id}}',
                'unit_ids' => [1],
            ],
            'description' => 'Link already-created units with `unit_ids`.',
        ]),
    ], 'UI: بيانات الوحدة. API: POST /realstate/step3. Units are optional.'),

    folder('5. Update Property', [
        req('Update Property Details', 'POST', '/realstate/update/step1', [
            'body' => array_merge([
                'id' => '{{real_estate_id}}',
                'instrument_type' => 'electronic',
                'instrument_history' => '1440-01-01',
                'type_instrument_history' => 'hijri',
            ], $step1Base, $manualAddress),
            'description' => 'Update step 1. For a new deed image, switch the body to form-data and attach `image_instrument`.',
        ]),
        req('Update Property Owner Details', 'POST', '/realstate/update/step2', [
            'body' => [
                'id' => '{{real_estate_id}}',
                'name_real_estate' => 'عقاري المحدّث',
                'type_dob_property_owner' => 'hijri',
                'name_owner' => 'اسم المالك',
                'property_owner_id_num' => '1234567890',
                'property_owner_dob_day' => '15',
                'property_owner_dob_month' => '06',
                'property_owner_dob_year' => '1410',
                'property_owner_mobile' => '0512345678',
                'add_legal_agent_of_owner' => false,
            ],
        ]),
        req('Update Property Units', 'POST', '/realstate/update/step3', [
            'body' => [
                'id' => '{{real_estate_id}}',
                'units' => [$unitItem],
            ],
            'description' => 'Replaces/syncs units on the property. Same shape as step 3.',
        ]),
    ], 'POST /realstate/update/step1|step2|step3'),

    folder('6. Units CRUD', [
        req('Get Units', 'GET', '/unit/index/{{real_estate_id}}'),
        req('Get All Units', 'GET', '/unit/all/{{real_estate_id}}'),
        req('Get Unit', 'GET', '/unit/show/{{unit_id}}'),
        req('Create Units', 'POST', '/unit/create', [
            'body' => [
                'real_estates_units_id' => '{{real_estate_id}}',
                'units' => [
                    $unitCreateItem,
                    array_merge($unitCreateItem, [
                        'unit_number' => '102',
                        'unit_area' => 90,
                        'tootal_rooms' => 2,
                    ]),
                ],
            ],
            'description' => "Create 1..50 units on an existing property.\n`contract_type` is `housing` (سكني) or `commercial` (تجاري).\nKitchen cabinets (`kitchen_tank` / `kitchen_cabinets`) require `The_number_of_kitchens` >= 1.\nA legacy flat single-unit body is still accepted.",
            'event' => $saveUnitIdScript,
        ]),
        req('Update Unit', 'POST', '/unit/update/{{unit_id}}', [
            'body' => [
                'unit_number' => '102',
                'floor_number' => 2,
                'unit_area' => 130,
                'split_ac' => 2,
                'furnished' => true,
            ],
        ]),
        req('Delete Unit', 'DELETE', '/unit/delete/{{unit_id}}'),
    ], 'Units on an existing property. `POST /unit/create` is the bulk-create endpoint.'),

    folder('7. Save from Contract', [
        req('Save Property from Contract', 'POST', '/save/property', [
            'body' => [
                'contract_id' => '{{contract_id}}',
                'name_real_estate' => 'عقاري المحفوظ',
            ],
            'description' => 'Copies a paid/completed contract into a saved property + units. Saves `real_estate_id`.',
            'event' => $saveRealEstateIdScript,
        ]),
    ], 'POST /save/property'),

    folder('Lookups', [
        req('Get Instrument Types', 'GET', '/instrument-types', ['public' => true]),
        req('Get Property Types', 'GET', '/real-estat-type', [
            'public' => true,
            'query' => $contractTypeQuery,
            'description' => '`contract_type` is required (`housing` or `commercial`).',
        ]),
        req('Get Property Usages', 'GET', '/real-estat-usage', [
            'public' => true,
            'query' => $contractTypeQuery,
            'description' => '`contract_type` is required (`housing` or `commercial`).',
        ]),
        req('Get Unit Types', 'GET', '/units-types', [
            'public' => true,
            'query' => $contractTypeQuery,
            'description' => '`contract_type` is required (`housing` or `commercial`).',
        ]),
        req('Get Unit Usages', 'GET', '/units-usage', [
            'public' => true,
            'query' => $contractTypeQuery,
            'description' => '`contract_type` is required (`housing` or `commercial`).',
        ]),
        req('Get Regions', 'GET', '/regions', ['public' => true]),
        req('Get Cities', 'GET', '/cities', [
            'public' => true,
            'query' => ['region_id' => '{{region_id}}'],
            'description' => '`region_id` is required.',
        ]),
    ], 'Reference data used while creating a property. Public, no auth.'),
];

$collection = [
    'info' => [
        '_postman_id' => REAL_ESTATE_COLLECTION_ID,
        'name' => 'AQDI Real Estate',
        'description' => <<<'MD'
عقاراتي — إنشاء وتعديل العقار المحفوظ ووحداته (API V2).

**Base:** `{{baseUrl}}/api/v2`  
**Auth:** Bearer `{{token}}`  
**Prefix:** `/realstate` (`/realState` is an alias, not duplicated here)

شغّل **0. Auth → Login** ثم **2. Step 1** حتى يُحفظ `real_estate_id`.

| UI | Endpoint |
|---|---|
| قائمة العقارات | `GET /realstate/index` |
| الخطوة الأولى — نوع المستند + العنوان | `POST /realstate/step1` |
| بيانات المالك | `POST /realstate/step2` |
| بيانات الوحدات | `POST /realstate/step3` |
| إضافة وحدات لاحقًا | `POST /unit/create` |
| حفظ عقار من عقد | `POST /save/property` |

رفع الملفات: الطلبات التي فيها صور تستخدم `form-data`. اختر الملف من Postman قبل الإرسال.

Generated by `php tools/generate_realestate_postman_collection.php`
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
        ['key' => 'contract_id', 'value' => '1', 'type' => 'default'],
        ['key' => 'region_id', 'value' => '1', 'type' => 'default'],
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

$collectionPath = $postmanDir.'/AQDI-Real-Estate.postman_collection.json';

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
