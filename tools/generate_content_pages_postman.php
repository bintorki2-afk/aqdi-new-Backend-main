<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Admin-Content-Pages-API.postman_collection.json
 * Run: php tools/generate_content_pages_postman.php
 */

$output = dirname(__DIR__).'/postman/AQDI-Admin-Content-Pages-API.postman_collection.json';

function uuidV4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/** @return array<string, mixed> */
function url(string $adminPath): array
{
    $adminPath = '/'.ltrim($adminPath, '/');
    $pathSegments = array_values(array_filter(explode('/', trim($adminPath, '/'))));

    return [
        'raw' => '{{baseUrl}}/api/admin'.$adminPath,
        'host' => ['{{baseUrl}}'],
        'path' => array_merge(['api', 'admin'], $pathSegments),
    ];
}

/** @return list<array<string, string>> */
function headers(bool $bearer = true): array
{
    $headers = [
        ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
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
            'header' => array_merge(headers(false), [
                ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
            ]),
            'url' => url('/employees/login'),
            'body' => [
                'mode' => 'raw',
                'raw' => json_encode([
                    'email' => 'admin@example.com',
                    'password' => 'password',
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'options' => ['raw' => ['language' => 'json']],
            ],
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

/** @return array<string, mixed> */
function getRequest(string $name, string $path, string $description): array
{
    return [
        'name' => $name,
        'request' => [
            'method' => 'GET',
            'header' => headers(),
            'url' => url($path),
            'description' => $description,
        ],
        'response' => [],
    ];
}

/**
 * @param  list<array<string, mixed>>  $formdata
 * @return array<string, mixed>
 */
function formDataRequest(string $name, string $path, string $description, array $formdata): array
{
    return [
        'name' => $name,
        'request' => [
            'method' => 'POST',
            'header' => headers(),
            'url' => url($path),
            'description' => $description,
            'body' => [
                'mode' => 'formdata',
                'formdata' => $formdata,
            ],
        ],
        'response' => [],
    ];
}

/** @return array<string, mixed> */
function textField(string $key, string $value): array
{
    return [
        'key' => $key,
        'value' => $value,
        'type' => 'text',
    ];
}

/** @return array<string, mixed> */
function fileField(string $key, string $src = ''): array
{
    return [
        'key' => $key,
        'type' => 'file',
        'src' => $src,
    ];
}

$homeFormData = [
    textField('page', 'home'),
    textField('meta_title', 'عقدي — توثيق عقد الإيجار إلكترونيًا'),
    textField('meta_description', 'وثّق عقد الإيجار عبر شبكة إيجار خلال دقائق مع عقدي.'),
    textField('hero[badge_text]', 'عقدك الموثق من شبكة ايجار خلال دقائق'),
    textField('hero[main_title]', 'عقد إيجار إلكتروني موثق'),
    textField('hero[description]', 'عقود إيجار معتمدة وموثقة عبر منصة إيجار الإلكترونية.'),
    textField('hero[keep_image]', '1'),
    fileField('hero[image]'),

    textField('official_authorities[badge_text]', 'جهات موثوقة ومعتمدة'),
    textField('official_authorities[main_title]', 'مرخصون من الجهات الرسمية'),
    textField('official_authorities[description]', 'نعمل وفق أنظمة معتمدة لضمان موثوقية وأمان جميع التعاملات.'),
    textField('official_authorities[cards][0][id]', '1'),
    textField('official_authorities[cards][0][title]', 'شبكة إيجار'),
    textField('official_authorities[cards][0][description]', 'منصة موثقة ومعتمدة رسميًا.'),
    textField('official_authorities[cards][0][keep_image]', '1'),
    fileField('official_authorities[cards][0][image]'),
    textField('official_authorities[cards][0][keep_license]', '1'),
    fileField('official_authorities[cards][0][license_file]'),
    textField('official_authorities[cards][0][license_file_type]', 'pdf'),

    textField('features[badge_text]', 'مميزاتنا'),
    textField('features[main_title]', 'لماذا عقدي!'),
    textField('features[description]', 'عقدك الموثق من شبكة إيجار والهيئة العامة للعقار خلال دقائق.'),
    textField('features[cards][0][id]', '1'),
    textField('features[cards][0][title]', 'ثقة عالية'),
    textField('features[cards][0][description]', 'منصة مرخصة من شبكة إيجار.'),
    textField('features[cards][0][keep_image]', '1'),
    fileField('features[cards][0][image]'),
    textField('features[cards][0][is_default]', '1'),

    textField('pricing[badge_text]', 'الأسعار'),
    textField('pricing[main_title]', 'وثّق عقدك'),
    textField('pricing[description]', 'اختر نوع العقد المناسب لك وابدأ التوثيق فورًا.'),
    textField('pricing[cards][0][id]', '1'),
    textField('pricing[cards][0][title]', 'عقد سكني'),
    textField('pricing[cards][0][subtitle]', 'مناسبة للإيجار، عقد فردي، عقد عائلي'),
    textField('pricing[cards][0][price]', '249'),
    textField('pricing[cards][0][duration_label]', '/ السنة الواحدة'),
    textField('pricing[cards][0][keep_image]', '1'),
    fileField('pricing[cards][0][image]'),
    textField('pricing[cards][0][is_default]', '1'),
    textField('pricing[cards][0][features][0][id]', '1'),
    textField('pricing[cards][0][features][0][text]', 'خلال دقائق ينجز عقدك'),

    textField('contact[badge_text]', 'دعم مباشر وشخصي'),
    textField('contact[main_title]', 'للاستفسار عن توثيق العقود على الواتساب'),
    textField('contact[description]', 'فريقنا المتخصص جاهز لمساعدتك.'),
    textField('contact[contact_number]', '0500000000'),
    textField('contact[keep_image]', '1'),
    fileField('contact[image]'),

    textField('app[badge_text]', 'تطبيق الجوال'),
    textField('app[main_title]', 'وثّق عقودك من هاتفك الذكي'),
    textField('app[description]', 'حمّل تطبيق عقدي وأنجز جميع معاملاتك.'),
    textField('app[keep_image]', '1'),
    fileField('app[image]'),

    textField('deleted_feature_card_ids[0]', '9'),
    textField('deleted_official_card_ids[0]', '5'),
    textField('deleted_pricing_feature_ids[0]', '17'),
];

$aboutFormData = [
    textField('page', 'about'),
    textField('meta_title', 'من نحن — عقدي'),
    textField('meta_description', 'تعرّف على عقدي ومنصة توثيق عقود الإيجار الإلكترونية.'),
    textField('hero[badge_text]', 'من نحن؟'),
    textField('hero[main_title]', 'نُبسّط إدارة العقود الإيجارية'),
    textField('hero[description]', 'تقدم عقاري حلولًا إلكترونية متكاملة.'),

    textField('story[badge_text]', 'قصتنا'),
    textField('story[main_title]', 'أرقام نعتز بها'),
    textField('story[description]', 'نعمل تحت إشراف وتراخيص الجهات الحكومية.'),
    textField('story[cards][0][id]', '1'),
    textField('story[cards][0][value]', '8.3M+'),
    textField('story[cards][0][label]', 'عدد العقود السكنية الموثقة'),

    textField('vision_mission[section_title]', 'كل ما تريد معرفته'),
    textField('vision_mission[section_description]', 'يمكنك استخدام منصة عقدي لتوثيق عقودك.'),
    textField('vision_mission[mission][badge_text]', 'الرسالة'),
    textField('vision_mission[mission][title]', 'رسالتنا'),
    textField('vision_mission[mission][description]', 'نقدم حلولًا عقارية موثوقة.'),
    textField('vision_mission[mission][keep_image]', '1'),
    fileField('vision_mission[mission][image]'),
    textField('vision_mission[vision][badge_text]', 'الرؤية'),
    textField('vision_mission[vision][title]', 'رؤيتنا'),
    textField('vision_mission[vision][description]', 'أن تكون إيجار الأولى.'),
    textField('vision_mission[vision][keep_image]', '1'),
    fileField('vision_mission[vision][image]'),

    textField('beneficiaries[badge_text]', 'العملية الإيجارية'),
    textField('beneficiaries[main_title]', 'المستفيدون من إيجار'),
    textField('beneficiaries[description]', 'يُطلق على المشتركين في إيجار العقاري.'),
    textField('beneficiaries[cards][0][id]', '1'),
    textField('beneficiaries[cards][0][title]', 'المستأجر'),
    textField('beneficiaries[cards][0][description]', 'يهدف من المواطن والمقيم.'),
    textField('beneficiaries[cards][0][keep_image]', '1'),
    fileField('beneficiaries[cards][0][image]'),

    textField('values[badge_text]', 'قيمنا'),
    textField('values[main_title]', 'قيم عقدي'),
    textField('values[description]', 'يهدف إيجار إلى تنظيم قطاع الإيجار العقاري.'),
    textField('values[cards][0][id]', '1'),
    textField('values[cards][0][title]', 'نحو إدارة واضحة ومسؤولة'),
    textField('values[cards][0][description]', 'نوفر معلومات واضحة ومحدثة.'),
    textField('values[cards][0][keep_image]', '1'),
    fileField('values[cards][0][image]'),
];

$collection = [
    'info' => [
        '_postman_id' => uuidV4(),
        'name' => 'AQDI Admin — Content Pages API',
        'description' => "Unified admin CMS endpoints for page-level content and Arabic SEO meta.\n\n**Authentication:** Bearer `{{employee_token}}` after `POST /api/admin/employees/login`\n\n**Endpoints:**\n- `GET /api/admin/content-pages/{pageKey}`\n- `POST /api/admin/content-pages/{pageKey}`\n\n**Supported page keys:** `home`, `about`, `faq` (`faqs` alias), `blogs`, `services`\n\n**Permissions:** `app_content` (home/about), `blogs` (blogs index), `analytics` (services index), `faqs` (FAQ index). System admin bypasses.\n\n**SEO fields (Arabic only):** `meta_title`, `meta_description`. POST is partial: omitted meta keys are kept; empty string clears. Meta-only POSTs do not wipe `home`/`about` sections.\n\n**POST body:** `multipart/form-data`. `blogs`, `services`, and `faq` are meta-only.",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000', 'type' => 'string'],
        ['key' => 'employee_token', 'value' => '', 'type' => 'string'],
        ['key' => 'pageKey', 'value' => 'home', 'type' => 'string'],
    ],
    'item' => [
        [
            'name' => 'Auth',
            'description' => 'احصل على token ثم خزنه في employee_token تلقائيًا.',
            'item' => [
                loginRequest(),
            ],
        ],
        [
            'name' => 'Home',
            'item' => [
                getRequest(
                    'Get home page content',
                    '/content-pages/home',
                    'Fetches the full editable home page structure.'
                ),
                formDataRequest(
                    'Upsert home page content',
                    '/content-pages/home',
                    'Creates the page if missing, or updates existing home sections and uploaded files.',
                    $homeFormData
                ),
            ],
        ],
        [
            'name' => 'About',
            'item' => [
                getRequest(
                    'Get about page content',
                    '/content-pages/about',
                    'Fetches the full editable about page structure.'
                ),
                formDataRequest(
                    'Upsert about page content',
                    '/content-pages/about',
                    'Creates the page if missing, or updates existing about sections and uploaded files.',
                    $aboutFormData
                ),
            ],
        ],
        [
            'name' => 'Blogs index SEO',
            'item' => [
                getRequest(
                    'Get blogs index SEO',
                    '/content-pages/blogs',
                    'Page-level SEO for the public blogs listing. `sections` is `{}`. Requires `blogs.view`.'
                ),
                formDataRequest(
                    'Update blogs index SEO',
                    '/content-pages/blogs',
                    'Partial meta update. Empty string clears the field. Requires `blogs.edit`.',
                    [
                        textField('meta_title', 'مدونة عقدي — مقالات عن توثيق الإيجار'),
                        textField('meta_description', 'اقرأ أحدث المقالات والأدلة حول توثيق عقود الإيجار إلكترونيًا.'),
                    ]
                ),
            ],
        ],
        [
            'name' => 'Services index SEO',
            'item' => [
                getRequest(
                    'Get services index SEO',
                    '/content-pages/services',
                    'Page-level SEO for the public services listing. `sections` is `{}`. Requires `analytics.view`.'
                ),
                formDataRequest(
                    'Update services index SEO',
                    '/content-pages/services',
                    'Partial meta update. Empty string clears the field. Requires `analytics.edit`.',
                    [
                        textField('meta_title', 'خدمات عقدي لتوثيق عقود الإيجار'),
                        textField('meta_description', 'تعرّف على خدمات توثيق العقود السكنية والتجارية عبر منصة عقدي.'),
                    ]
                ),
            ],
        ],
        [
            'name' => 'FAQs index SEO',
            'item' => [
                getRequest(
                    'Get FAQs index SEO',
                    '/content-pages/faq',
                    'Page-level SEO for the public FAQ listing. `sections` is `{}`. Requires `faqs.view`. Canonical key is `faq` (`/faqs` also works).'
                ),
                formDataRequest(
                    'Update FAQs index SEO',
                    '/content-pages/faq',
                    'Partial meta update. Empty string clears the field. Requires `faqs.edit`.',
                    [
                        textField('meta_title', 'الأسئلة الشائعة — عقدي'),
                        textField('meta_description', 'إجابات عن توثيق عقود الإيجار إلكترونيًا عبر منصة عقدي وشبكة إيجار.'),
                    ]
                ),
            ],
        ],
        [
            'name' => 'Home / About SEO only',
            'item' => [
                formDataRequest(
                    'Update home SEO without wiping sections',
                    '/content-pages/home',
                    'Posts only meta fields. Existing home sections are preserved.',
                    [
                        textField('meta_title', 'عقدي — توثيق عقد الإيجار إلكترونيًا'),
                        textField('meta_description', 'وثّق عقد الإيجار عبر شبكة إيجار خلال دقائق مع عقدي.'),
                    ]
                ),
                formDataRequest(
                    'Update about SEO without wiping sections',
                    '/content-pages/about',
                    'Posts only meta fields. Existing about sections are preserved.',
                    [
                        textField('meta_title', 'من نحن — عقدي'),
                        textField('meta_description', 'تعرّف على عقدي ومنصة توثيق عقود الإيجار الإلكترونية.'),
                    ]
                ),
            ],
        ],
        [
            'name' => 'Generic',
            'item' => [
                getRequest(
                    'Get by pageKey variable',
                    '/content-pages/{{pageKey}}',
                    'Use the `pageKey` collection variable: `home`, `about`, `faq`, `blogs`, or `services`.'
                ),
            ],
        ],
    ],
];

file_put_contents(
    $output,
    json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
);

echo "Generated {$output}\n";
