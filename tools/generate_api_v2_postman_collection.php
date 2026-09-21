<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-API-V2.postman_collection.json
 * covering API V2 routes only (routes/api_v2.php + module api_v2.php files).
 *
 * Organized by business domain, not controllers. Each endpoint appears once.
 *
 * Run: php tools/generate_api_v2_postman_collection.php
 */

$basePath = dirname(__DIR__);

const COLLECTION_ID = '83d38f3e-f58e-4895-9bae-6913fabc9c17';

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
    $segments = array_values(array_filter(explode('/', $path), fn ($s) => $s !== ''));
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

/**
 * @param  list<array<string, mixed>>  $items
 */
function countRequests(array $items): int
{
    $count = 0;
    foreach ($items as $item) {
        if (isset($item['request'])) {
            $count++;
        } elseif (isset($item['item']) && is_array($item['item'])) {
            $count += countRequests($item['item']);
        }
    }

    return $count;
}

$saveIdsScript = [
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
                'const contractId = json?.data?.contract?.id || json?.data?.contract_id || json?.data?.id;',
                'if (contractId && String(pm.request.url).includes("/contract")) {',
                '    pm.collectionVariables.set("contract_id", String(contractId));',
                '}',
                'const uuid = json?.data?.contract?.uuid || json?.data?.uuid;',
                'if (uuid) {',
                '    pm.collectionVariables.set("contract_uuid", uuid);',
                '}',
                'const realId = json?.data?.real_estate?.id || json?.data?.id;',
                'if (realId && String(pm.request.url).includes("realstate")) {',
                '    pm.collectionVariables.set("real_estate_id", String(realId));',
                '}',
                'const unitId = json?.data?.created?.[0]?.id || json?.data?.id;',
                'if (unitId && String(pm.request.url).includes("/unit/create")) {',
                '    pm.collectionVariables.set("unit_id", String(unitId));',
                '}',
            ],
        ],
    ],
];

$contractTypeQuery = ['contract_type' => 'housing'];

$realEstateStep1Body = [
    'contract_type' => 'housing',
    'contract_ownership' => 'owner',
    'instrument_type' => 'electronic',
    'instrument_history' => '1440-01-01',
    'type_instrument_history' => 'hijri',
    'property_type_id' => 1,
    'property_usages_id' => 1,
    'number_of_floors' => 2,
    'number_of_units_in_realestate' => '4',
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

$realEstateStep2Body = [
    'id' => '{{real_estate_id}}',
    'type_dob_property_owner' => 'hijri',
    'name_owner' => 'اسم المالك',
    'property_owner_id_num' => '1234567890',
    'property_owner_dob_day' => '15',
    'property_owner_dob_month' => '06',
    'property_owner_dob_year' => '1410',
    'property_owner_mobile' => '0512345678',
    'property_owner_iban' => 'SA0380000000608010167500',
];

$unitItem = [
    'unit_type_id' => 1,
    'unit_usage_id' => 1,
    'unit_number' => '101',
    'floor_number' => 1,
    'unit_area' => 120,
    'kitchen_tank' => 0,
    'furnished' => 0,
    'electricity_meter' => 1,
    'water_meter' => 1,
];

$unitCreateItem = [
    'contract_type' => 'housing',
    'unit_type_id' => 1,
    'unit_usage_id' => 1,
    'unit_number' => '101',
    'floor_number' => 1,
    'unit_area' => 120,
    'tootal_rooms' => 3,
    'The_number_of_toilets' => 2,
    'The_number_of_kitchens' => 1,
    'split_ac' => 0,
    'window_ac' => 0,
    'kitchen_tank' => 0,
    'furnished' => 0,
    'electricity_meter' => 0,
    'water_meter' => 0,
];

$items = [
    folder('Auth', [
        req('Login', 'POST', '/auth/login', [
            'public' => true,
            'body' => [
                'mobile' => '0512345678',
                'password' => 'password123',
                'fcm_token' => 'optional-fcm-token',
            ],
            'description' => 'Saves `token` on success. If a pending custom coupon exists, `data.login_notification` contains the login popup.',
            'event' => $saveIdsScript,
        ]),
        req('Signup', 'POST', '/auth/signup', [
            'public' => true,
            'body' => [
                'fname' => 'محمد',
                'mobile' => '0599999999',
                'email' => 'user@example.com',
                'password' => 'password123',
                'fcm_token' => 'optional-fcm-token',
            ],
        ]),
        req('Verify Account', 'POST', '/auth/verification', [
            'public' => true,
            'body' => [
                'mobile' => '00966599999999',
                'verification_code' => '1234',
            ],
        ]),
        req('Resend Verification', 'POST', '/auth/resend', [
            'public' => true,
            'body' => ['mobile' => '0512345678'],
        ]),
        req('Forgot Password', 'POST', '/auth/forgot-password', [
            'public' => true,
            'body' => ['mobile' => '0512345678'],
        ]),
        req('Confirm Reset Password Code', 'POST', '/auth/reset-password-code', [
            'public' => true,
            'body' => [
                'mobile' => '00966512345678',
                'code' => '123456',
            ],
        ]),
        req('Reset Password', 'POST', '/auth/reset-password', [
            'public' => true,
            'body' => [
                'mobile' => '00966512345678',
                'code' => '123456',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ],
        ]),
        req('Logout', 'POST', '/auth/logout'),
    ], 'Login, signup, verification, password reset, and logout.'),

    folder('Users', [
        req('Get Profile', 'GET', '/profile'),
        req('Update Profile', 'POST', '/profile', [
            'body' => [
                'fname' => 'محمد',
                'email' => 'updated@example.com',
                'mobile' => '0512345678',
            ],
            'description' => 'Optional `photo` file — switch the body to form-data to upload an image.',
        ]),
        req('Update Password', 'POST', '/update/password', [
            'body' => [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ],
        ]),
        req('Update FCM Token', 'POST', '/fcm', [
            'body' => ['fcm_token' => 'device-fcm-token'],
        ]),
        req('Deactivate Account', 'POST', '/user/deactivate'),
    ], 'Authenticated account profile and device token operations.'),

    folder('Notifications', [
        req('Get Notifications', 'GET', '/notifications'),
    ], 'User notification inbox.'),

    folder('Contracts', [
        folder('Contracts', [
            req('Get Contracts', 'GET', '/contracts'),
            req('Get Contract', 'GET', '/contracts/{{contract_id}}'),
            req('Create Contract', 'POST', '/contract/start', [
                'body' => [
                    'contract_type' => 'housing',
                    'instrument_type' => 'old_handwritten',
                    'is_real' => false,
                ],
                'description' => "Creates a contract and starts the workflow. Then complete steps under Contract Steps.\n\nFrom a saved property, send `is_real: true`, `real_id`, and `real_units_id` (or `unit_ids`).",
                'event' => $saveIdsScript,
            ]),
            req('Delete Contract', 'DELETE', '/contracts/{{contract_id}}', [
                'description' => 'Soft-deletes the contract. Only the owner can delete. Completed contracts cannot be deleted.',
            ]),
            req('Search Contracts', 'GET', '/search/{{search_term}}', [
                'description' => 'Search by tenant or owner national ID.',
            ]),
            req('Get Contracts by Status', 'GET', '/contracts/status/{{status_id}}'),
            req('Get Draft Contracts', 'GET', '/contracts/draft'),
            req('Get Draft Contracts by Status', 'GET', '/contracts/draft/status/{{draft_status_id}}'),
            req('Save Contract Draft', 'POST', '/contract/draft', [
                'body' => [
                    'id' => '{{contract_id}}',
                    'is_draft' => true,
                ],
                'description' => 'Marks the contract as a draft (`true`) or not (`false`).',
            ]),
        ], 'Core contract operations. Workflow steps live under Contract Steps.'),

        folder('Contract Steps', [
            req('Complete Property Details', 'POST', '/contract/step1', [
                'body' => [
                    'id' => '{{contract_id}}',
                    'number_of_floors' => 2,
                    'property_type_id' => 1,
                    'property_usages_id' => 1,
                    'number_of_units_in_realestate' => 4,
                ],
                'description' => 'Contract workflow step 1. Instrument image fields use multipart/form-data.',
            ]),
            req('Complete Address', 'POST', '/contract/step2', [
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
                'description' => 'Contract workflow step 2.',
            ]),
            req('Complete Owner Details', 'POST', '/contract/step3', [
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
                    'add_legal_agent_of_owner' => false,
                    'notes_edits' => 'ملاحظات التعديلات — حقل اختياري',
                ],
                'description' => "Contract workflow step 3. `add_legal_agent_of_owner` defaults to false.\nWhen true, send agent identity fields and `copy_of_the_authorization_or_agency` as a file.",
            ]),
            req('Complete Tenant Details', 'POST', '/contract/step4', [
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
                'description' => 'Contract workflow step 4.',
            ]),
            req('Complete Unit Details', 'POST', '/contract/step5', [
                'body' => [
                    'id' => '{{contract_id}}',
                    'units' => [$unitItem],
                ],
                'description' => 'Contract workflow step 5. Prefer `units[]`. A legacy flat payload (`unit_number`, `unit_type_id`, …) is still accepted.',
            ]),
            req('Complete Terms and Payment', 'POST', '/contract/step6', [
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
                'description' => "Contract workflow step 6. Prefer `tenant_role_ids`. Legacy `tenant_role_id` is still accepted.\nFor a custom duration, send `duration_preset: other` with `duration_years` / `duration_months` instead of `contract_term_in_years`.",
            ]),
            req('Check Uncompleted Contract', 'GET', '/contract/check-uncompleted-contract', [
                'query' => ['contract_type' => 'housing'],
                'description' => 'Required query `contract_type=housing|commercial`. Returns whether the user has an incomplete contract of that type only (a housing draft does not block starting a commercial contract). When check=true: `contract_id`, `uuid`, `step`, `contract_type`.',
            ]),
            req('Get Current Contract Step', 'POST', '/contract/uncompleted-contract', [
                'body' => ['uuid' => '{{contract_uuid}}'],
                'description' => 'Resume an incomplete contract. Returns the current step plus saved data for previous steps.',
            ]),
        ], 'Contract lifecycle and workflow. Core CRUD stays under Contracts.'),

        folder('Contract Details', [
            req('Get Contract Documents', 'GET', '/getContracts/{{contract_uuid}}', [
                'description' => 'Paid contract files for the given uuid. Returns a wait message when files are not ready.',
            ]),
        ], 'Detailed information belonging to a specific contract. Get Contract itself stays under Contracts.'),

        folder('Contract Relations', [
            folder('Contract Types', [
                req('Get Contract Types', 'GET', '/contract-types', ['public' => true]),
            ]),
            folder('Instrument Types', [
                req('Get Instrument Types', 'GET', '/instrument-types', ['public' => true]),
            ]),
            folder('Instrument Type Settings', [
                req('Get Instrument Type Settings', 'GET', '/setting-contracts', [
                    'public' => true,
                    'query' => ['instrument_type' => 'electronic'],
                    'description' => 'Optional `instrument_type` filter. Alias prefix: `/instrument-type-settings`.',
                ]),
                req('Get Instrument Type Setting', 'GET', '/setting-contracts/{{setting_contract_id}}', [
                    'public' => true,
                    'description' => 'Alias prefix: `/instrument-type-settings/{id}`.',
                ]),
            ]),
            folder('Property Types', [
                req('Get Property Types', 'GET', '/real-estat-type', [
                    'public' => true,
                    'query' => $contractTypeQuery,
                    'description' => '`contract_type` is required (`housing` or `commercial`).',
                ]),
            ]),
            folder('Property Usages', [
                req('Get Property Usages', 'GET', '/real-estat-usage', [
                    'public' => true,
                    'query' => $contractTypeQuery,
                    'description' => '`contract_type` is required (`housing` or `commercial`).',
                ]),
            ]),
            folder('Unit Types', [
                req('Get Unit Types', 'GET', '/units-types', [
                    'public' => true,
                    'query' => $contractTypeQuery,
                    'description' => '`contract_type` is required (`housing` or `commercial`).',
                ]),
            ]),
            folder('Unit Usages', [
                req('Get Unit Usages', 'GET', '/units-usage', [
                    'public' => true,
                    'query' => $contractTypeQuery,
                    'description' => '`contract_type` is required (`housing` or `commercial`).',
                ]),
            ]),
            folder('Contract Periods', [
                req('Get Contract Periods', 'GET', '/contract-periods', [
                    'public' => true,
                    'query' => $contractTypeQuery,
                    'description' => '`contract_type` is required (`housing` or `commercial`).',
                ]),
            ]),
            folder('Payment Types', [
                req('Get Payment Types', 'GET', '/payments-types', [
                    'public' => true,
                    'query' => $contractTypeQuery,
                    'description' => 'Payment schedules referenced by contracts. `contract_type` is required.',
                ]),
            ]),
            folder('Tenant Roles', [
                req('Get Tenant Roles', 'GET', '/tenant-roles', [
                    'public' => true,
                    'description' => 'Only list is implemented on V2. Other tenant-role verbs in the route file are not wired.',
                ]),
            ]),
            folder('Paperwork', [
                req('Get Paperwork', 'GET', '/paperwork', [
                    'public' => true,
                    'query' => $contractTypeQuery,
                    'description' => '`contract_type` is required (`housing` or `commercial`).',
                ]),
            ]),
            folder('Properties', [
                req('Get Properties', 'GET', '/realstate/index', [
                    'description' => 'Canonical prefix is `/realstate`. `/realState` is an alias and is not duplicated here.',
                ]),
                req('Get All Properties', 'GET', '/realstate/all'),
                req('Get Property', 'GET', '/realstate/show/{{real_estate_id}}'),
                req('Get Property Units', 'GET', '/realstate/units/{{real_estate_id}}'),
                req('Create Property', 'POST', '/realstate/step1', [
                    'body' => $realEstateStep1Body,
                    'description' => 'Property workflow step 1 (details + location). Multipart for `image_instrument`, `image_address`, and endowment files.',
                    'event' => $saveIdsScript,
                ]),
                req('Complete Property Owner Details', 'POST', '/realstate/step2', [
                    'body' => $realEstateStep2Body,
                    'description' => 'Property workflow step 2. `add_legal_agent_of_owner` defaults to false.',
                ]),
                req('Complete Property Units', 'POST', '/realstate/step3', [
                    'body' => [
                        'id' => '{{real_estate_id}}',
                        'units' => [$unitItem],
                    ],
                    'description' => 'Property workflow step 3. `units` may be omitted to finish the property without attaching units yet.',
                ]),
                req('Update Property', 'POST', '/realstate/update/step1', [
                    'body' => array_merge(['id' => '{{real_estate_id}}'], $realEstateStep1Body),
                ]),
                req('Update Property Owner Details', 'POST', '/realstate/update/step2', [
                    'body' => $realEstateStep2Body,
                ]),
                req('Update Property Units', 'POST', '/realstate/update/step3', [
                    'body' => [
                        'id' => '{{real_estate_id}}',
                        'units' => [$unitItem],
                    ],
                ]),
                req('Delete Property', 'DELETE', '/realstate/delete/{{real_estate_id}}'),
                req('Save Property from Contract', 'POST', '/save/property', [
                    'body' => [
                        'contract_id' => '{{contract_id}}',
                        'name_real_estate' => 'عقاري المحفوظ',
                    ],
                ]),
            ], 'Saved properties used as supporting data when creating contracts.'),
            folder('Units', [
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
                    'description' => 'Create one or more units on an existing property in a single request. Send `units` with 1..50 items. `contract_type` is `housing` (سكني) or `commercial` (تجاري). Kitchen cabinets (`kitchen_tank` or `kitchen_cabinets`) require `The_number_of_kitchens` >= 1. A legacy flat single-unit body is still accepted.',
                    'event' => $saveIdsScript,
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
            ]),
        ], 'مرتبطات العقد — supporting entities and reference data for contracts, not the contract itself.'),

        folder('Financial', [
            req('Get Contract Financial Details', 'GET', '/financial/{{contract_uuid}}'),
            req('Get Contract Financial Summary', 'GET', '/finance-summary/{{contract_uuid}}', [
                'description' => 'Same financial payload as Get Contract Financial Details, exposed on this URL.',
            ]),
            req('Preview Documentation Fee', 'POST', '/contract/doc-fee', [
                'body' => [
                    'contract_type' => 'housing',
                    'duration_preset' => 'other',
                    'duration_years' => 1,
                    'duration_months' => 1,
                ],
                'description' => 'Preview rental documentation fees without saving. Housing 249+150/year, commercial 349+500/year. Any part of a year counts as a year. You may send `id` instead of `contract_type`.',
            ]),
            req('Get Service Fees', 'GET', '/services-pricing', [
                'public' => true,
                'query' => $contractTypeQuery,
                'description' => 'Service fee catalog by contract type. `contract_type` is required.',
            ]),
            req('Get Meter Fee Settings', 'GET', '/meter-fee-settings', [
                'public' => true,
            ]),
        ], 'Financial information and fee calculations for contracts. Invoice and payment operations have their own folders.'),

        folder('Invoices', [
            req('Get Invoices', 'GET', '/invoices'),
            req('Get Invoice', 'GET', '/invoices/number/{{invoice_number}}', [
                'description' => 'Lookup by invoice number, e.g. `INV-47990`.',
            ]),
            req('Get Contract Invoice', 'GET', '/invoices/{{contract_id}}', [
                'description' => 'Invoice for a contract, by contract id.',
            ]),
            req('Get Contract Invoice by Path', 'GET', '/contracts/{{contract_id}}/invoice', [
                'description' => 'Same invoice as Get Contract Invoice, on the `/contracts/{id}/invoice` URL.',
            ]),
        ], 'Invoice-specific operations. Not duplicated under Financial.'),

        folder('Payments', [
            req('Get Payment', 'GET', '/payment/{{contract_uuid}}', [
                'public' => true,
                'description' => 'Returns the hosted payment URL for the contract. No auth.',
            ]),
            req('Get Payment Result', 'GET', '/payment/result/{{contract_uuid}}', [
                'public' => true,
                'description' => 'Public payment status for success/failed screens.',
            ]),
            req('Sync Payment', 'GET', '/payment/sync/{{contract_uuid}}', [
                'public' => true,
                'description' => 'Refresh payment status from the gateway.',
            ]),
            req('Confirm Payment', 'POST', '/status/{{contract_uuid}}/success', [
                'public' => true,
                'body' => ['status' => 'paid'],
                'description' => 'Payment IPN/webhook. No auth. Payload varies by gateway.',
            ]),
            req('Handle Payment Return', 'POST', '/status/{{contract_uuid}}', [
                'public' => true,
                'body' => [],
                'description' => 'Payment gateway return POST. No auth.',
            ]),
            req('Get Payment Redirect Result', 'GET', '/status/result/{{contract_uuid}}', [
                'public' => true,
            ]),
            req('Get Payment Success Redirect', 'GET', '/status/success/{{contract_uuid}}', [
                'public' => true,
            ]),
            req('Get Payment Error Redirect', 'GET', '/status/error/{{contract_uuid}}', [
                'public' => true,
            ]),
            req('Get Payment Messages', 'GET', '/payment-messages', [
                'public' => true,
                'query' => ['type' => 'success'],
                'description' => 'Success/failed payment screen copy. Omit `type` to return both. Same handler as Get Payment Content.',
            ]),
            req('Get Payment Content', 'GET', '/payment-content', [
                'public' => true,
                'query' => ['type' => 'success'],
                'description' => 'Same payload as Get Payment Messages, on `/payment-content`.',
            ]),
            req('Get Bank Accounts', 'GET', '/bank-accounts', [
                'public' => true,
            ]),
        ], 'Payment operations and payment-screen content. Not duplicated under Contracts or Financial.'),
    ], "Contract domain.\n\n- Contracts — core contract operations\n- Contract Steps — lifecycle / workflow\n- Contract Details — documents and details of a contract\n- Contract Relations — مرتبطات العقد (types, properties, units, lookups)\n- Financial — amounts, fees, summaries\n- Invoices — invoice operations\n- Payments — payment operations"),

    folder('Coupons', [
        req('Get My Coupons', 'GET', '/coupons/mine', [
            'description' => 'Pending user-assigned coupons plus the login notification payload.',
        ]),
        req('Acknowledge Login Coupon Notification', 'POST', '/coupons/login-notification/ack', [
            'body' => ['user_coupon_id' => '{{user_coupon_id}}'],
            'description' => 'Marks the login coupon popup as seen. Omit `user_coupon_id` to acknowledge all.',
        ]),
        req('Apply Coupon', 'POST', '/Coupon/{{contract_uuid}}', [
            'body' => ['code_coupon' => 'DISCOUNT10'],
        ]),
    ], 'User coupons and applying a coupon to a contract.'),

    folder('Locations', [
        req('Get Regions', 'GET', '/regions', ['public' => true]),
        req('Get Cities', 'GET', '/cities', [
            'public' => true,
            'query' => ['region_id' => '{{region_id}}'],
            'description' => '`region_id` is required.',
        ]),
    ], 'Generic location lookups used across the app, not only contracts.'),

    folder('Content', [
        req('Get Terms and Conditions', 'GET', '/terms-and-conditions', ['public' => true]),
        req('Get Privacy Policy', 'GET', '/privacy', ['public' => true]),
        req('Get FAQ', 'GET', '/common-questions', ['public' => true]),
        req('Get All Content Page SEO', 'GET', '/content-pages', [
            'public' => true,
            'description' => 'Every listing page: `home`, `about`, `faq`, `blogs`, `services`. Each value is `{ page, meta_title, meta_description }`.',
        ]),
        req('Get Content Page', 'GET', '/content-pages/{{page_key}}', [
            'public' => true,
            'description' => '`GET /content-pages` returns every page: `{ home, about, faq, blogs, services }` each with `page`, `meta_title`, `meta_description`. `GET /content-pages/{page_key}` is the same for one page. Keys: `home`, `about`, `faq` (`faqs` alias), `blogs`, `services`. Empty string falls back on the public site.',
        ]),
        req('Get Cover Image', 'GET', '/cover', ['public' => true]),
        req('Get Instruction Images', 'GET', '/instruction-images', ['public' => true]),
        req('Get Instruction Image', 'GET', '/instruction-images/{{instruction_image_key}}', [
            'public' => true,
            'description' => 'Example key: `step1-instrument`.',
        ]),
        req('Get Contract Popups', 'GET', '/popup-contracts', [
            'public' => true,
            'query' => [
                'instrument_type' => 'electronic',
                'context' => 'contract',
            ],
            'description' => 'Optional `instrument_type` and `context` (`contract` or `realestate`).',
        ]),
    ], 'Public content pages, FAQs, instructional images, and contract popups.'),

    folder('Settings', [
        req('Get App Settings', 'GET', '/settings', ['public' => true]),
        req('Get App Status', 'GET', '/app-status', [
            'public' => true,
            'query' => [
                'platform' => 'ios',
                'current_version' => '1.0.3',
            ],
            'description' => 'Optional `platform` and `current_version` (or `version`) to compute force/optional update. `platform=website` is also valid.',
        ]),
        req('Get Website Status', 'GET', '/website-status', [
            'public' => true,
            'description' => '200 when the website is open, 503 when closed. Send header `X-Client: website` on other `/api/v2` calls so they also 503 while the site is closed.',
        ]),
        req('Get SMS Settings', 'GET', '/sms-settings', ['public' => true]),
    ], 'App, website, and SMS settings. Meter fees live under Contracts → Financial.'),
];

$collectionVars = [
    ['key' => 'baseUrl', 'value' => 'http://localhost:8000', 'type' => 'default'],
    ['key' => 'token', 'value' => '', 'type' => 'secret'],
    ['key' => 'contract_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'contract_uuid', 'value' => '', 'type' => 'default'],
    ['key' => 'real_estate_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'unit_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'search_term', 'value' => '101', 'type' => 'default'],
    ['key' => 'status_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'draft_status_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'region_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'invoice_number', 'value' => 'INV-47990', 'type' => 'default'],
    ['key' => 'user_coupon_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'page_key', 'value' => 'home', 'type' => 'default'],
    ['key' => 'setting_contract_id', 'value' => '1', 'type' => 'default'],
    ['key' => 'instruction_image_key', 'value' => 'step1-instrument', 'type' => 'default'],
];

$requestCount = countRequests($items);

$collection = [
    'info' => [
        '_postman_id' => COLLECTION_ID,
        'name' => 'AQDI API V2',
        'description' => <<<'MD'
API V2 only. Organized by business domain → feature → operation.

**Base path:** `{{baseUrl}}/api/v2`

**Auth:** Bearer `{{token}}` on protected requests. Public folders use no auth.

**Quick start**
1. Set `baseUrl` (e.g. `http://localhost:8000`)
2. Run **Auth → Login** — token is saved automatically
3. Create a contract or property; IDs are saved when the response includes them

**Where to look**
- Auth / Users / Notifications — account and inbox
- Contracts — core contract operations
- Contract Steps — workflow (`/contract/step*`)
- Contract Details — documents of a contract
- Contract Relations — مرتبطات العقد (types, properties, units, lookups)
- Financial / Invoices / Payments — money flows (each endpoint once)
- Coupons, Locations, Content, Settings — remaining public/app features

V1 is not duplicated here. Prefix aliases (`/realState`, `/instrument-type-settings`) are documented on the canonical request instead of copied.

File uploads: switch the body to `form-data` in Postman.

Generated by `php tools/generate_api_v2_postman_collection.php`.
MD,
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

$collectionPath = $postmanDir.'/AQDI-API-V2.postman_collection.json';

file_put_contents(
    $collectionPath,
    json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

echo "Wrote {$collectionPath}\n";
echo 'Requests: '.$requestCount."\n";
