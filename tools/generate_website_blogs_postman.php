<?php

declare(strict_types=1);

/**
 * Generates postman/AQDI-Website-Blogs-API.postman_collection.json
 *
 * Public website / blog-frontend JSON API (`/api/blogs`)
 * plus SEO CMS write endpoints on the same resource.
 *
 * Run: php tools/generate_website_blogs_postman.php
 */

$output = dirname(__DIR__).'/postman/AQDI-Website-Blogs-API.postman_collection.json';

function uuidV4(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * @param  array<int, array{key: string, value: string, disabled?: bool}>  $query
 * @return array<string, mixed>
 */
function url(string $path, array $query = [], string $prefix = 'api'): array
{
    $path = '/'.ltrim($path, '/');
    $pathSegments = array_values(array_filter(explode('/', trim($path, '/')), static fn ($s) => $s !== ''));

    $fullPath = $prefix === ''
        ? $pathSegments
        : array_merge(explode('/', $prefix), $pathSegments);

    $result = [
        'raw' => '{{baseUrl}}/'.implode('/', $fullPath),
        'host' => ['{{baseUrl}}'],
        'path' => $fullPath,
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

/**
 * @return list<array<string, string>>
 */
function headers(bool $bearer = false, bool $json = false): array
{
    $headers = [
        ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
        ['key' => 'Accept-Language', 'value' => '{{locale}}', 'type' => 'text'],
    ];

    if ($json) {
        $headers[] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
    }

    if ($bearer) {
        $headers[] = ['key' => 'Authorization', 'value' => 'Bearer {{seo_token}}', 'type' => 'text'];
    }

    return $headers;
}

/**
 * @param  list<string>|null  $testScript
 * @param  array<int, array{key: string, value: string, disabled?: bool}>  $query
 * @param  array<string, mixed>|null  $jsonBody
 * @param  list<array<string, mixed>>|null  $formdata
 * @return array<string, mixed>
 */
function requestItem(
    string $name,
    string $method,
    string $path,
    string $description,
    bool $bearer = false,
    ?array $jsonBody = null,
    ?array $formdata = null,
    array $query = [],
    ?array $testScript = null
): array {
    $method = strtoupper($method);

    $request = [
        'method' => $method,
        'header' => headers($bearer, $jsonBody !== null),
        'url' => url($path, $query),
        'description' => $description,
    ];

    if ($jsonBody !== null) {
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($jsonBody, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'options' => ['raw' => ['language' => 'json']],
        ];
    } elseif ($formdata !== null) {
        $request['body'] = [
            'mode' => 'formdata',
            'formdata' => $formdata,
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

/** @return array<string, mixed> */
function textField(string $key, string $value, bool $disabled = false): array
{
    $field = [
        'key' => $key,
        'value' => $value,
        'type' => 'text',
    ];
    if ($disabled) {
        $field['disabled'] = true;
    }

    return $field;
}

/** @return array<string, mixed> */
function fileField(string $key): array
{
    return [
        'key' => $key,
        'type' => 'file',
        'src' => '',
    ];
}

$saveSeoToken = [
    'const json = pm.response.json();',
    'const token = json?.data?.token ?? json?.token;',
    'if (token) {',
    '  pm.collectionVariables.set("seo_token", token);',
    '}',
];

$saveBlogFromList = [
    'const json = pm.response.json();',
    'const items = Array.isArray(json?.data) ? json.data : [];',
    'const first = items[0];',
    'if (first?.slug) { pm.collectionVariables.set("blog_slug", String(first.slug)); }',
    'if (first?.id) { pm.collectionVariables.set("blog_id", String(first.id)); }',
];

$saveBlogFromCreate = [
    'const json = pm.response.json();',
    'const blog = json?.data;',
    'if (blog?.id) { pm.collectionVariables.set("blog_id", String(blog.id)); }',
    'if (blog?.slug) { pm.collectionVariables.set("blog_slug", String(blog.slug)); }',
];

$createForm = [
    textField('title', 'عنوان مقال تجريبي'),
    textField('description', '<p>محتوى المقال يظهر على صفحة المدونة في الموقع.</p>'),
    textField('status', 'published'),
    textField('is_active', '1'),
    textField('image_alt', 'وصف صورة المقال'),
    textField('meta_title', 'عنوان SEO'),
    textField('meta_description', 'وصف SEO يظهر في نتائج البحث'),
    textField('category', 'guides'),
    textField('category_label_ar', 'أدلة'),
    textField('author', 'فريق عقدي'),
    textField('excerpt', 'مقتطف يظهر على بطاقة المقال في القائمة.'),
    textField('is_featured', '0'),
    textField('tags', 'ijar,guides'),
    textField('publish_at', '', true),
    fileField('image'),
];

$updateForm = [
    textField('title', 'عنوان مقال محدّث'),
    textField('description', '<p>محتوى محدّث للمقال.</p>'),
    textField('status', 'published'),
    textField('is_active', '1'),
    textField('image_alt', 'وصف صورة محدّث'),
    textField('meta_title', 'عنوان SEO محدّث'),
    textField('meta_description', 'وصف SEO محدّث'),
    fileField('image'),
];

$collection = [
    'info' => [
        '_postman_id' => uuidV4(),
        'name' => 'AQDI Website — Blogs API',
        'description' => implode("\n", [
            'Public JSON API for website / blog-frontend blogs (`/api/blogs`).',
            '',
            '**Public (no token)**',
            '- `GET /api/blogs` — published / due-scheduled, active. Query: `page`, `category`, `tag`, `search`, `per_page`, `sort=latest|popular`, `featured=1`, `fields=`',
            '- Envelope: `{ data: BlogListItem[], meta: { current_page, last_page, per_page, total } }`',
            '- `GET /api/blogs/meta` — categories, popular tags, marketing stats',
            '- `GET /api/blogs/{slug}` — single post. `?preview=1` or `X-No-Track: 1` skips views_count',
            '- `POST /api/newsletter` — `{ email }`',
            '',
            '**SEO CMS (Bearer `{{seo_token}}`)** — unchanged',
            '- `POST /api/seo/login` then create / update / toggle / delete',
            '',
            'Admin CRUD stays on `/api/admin/blogs` (see AQDI Admin API). This collection is the public website surface.',
            '',
            '1. Import this collection.',
            '2. Set `baseUrl` (e.g. http://localhost:8000).',
            '3. Run **List published blogs** — saves `blog_slug` / `blog_id`.',
            '4. For writes, run **SEO login** first (seed: `seo@email.com` / `seo123456`).',
        ]),
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'baseUrl', 'value' => 'http://localhost:8000', 'type' => 'string'],
        ['key' => 'locale', 'value' => 'ar', 'type' => 'string'],
        ['key' => 'seo_token', 'value' => '', 'type' => 'string'],
        ['key' => 'blog_slug', 'value' => 'how-to-certify-rental-contract-online', 'type' => 'string'],
        ['key' => 'blog_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'seo_email', 'value' => 'seo@email.com', 'type' => 'string'],
        ['key' => 'seo_password', 'value' => 'seo123456', 'type' => 'string'],
    ],
    'item' => [
        [
            'name' => '1 — Public JSON (website / blog frontend)',
            'description' => 'Active published posts, or scheduled posts whose publish_at has passed. Default 6 per page. List envelope is `{ data, meta }`.',
            'item' => [
                requestItem(
                    'List published blogs',
                    'GET',
                    '/blogs',
                    'Returns published (or due scheduled) active blogs as BlogListItem[] (no HTML body). Envelope: data + meta. Saves first item slug/id. Empty list is 200 with data [].',
                    false,
                    null,
                    null,
                    [
                        ['key' => 'page', 'value' => '1'],
                        ['key' => 'per_page', 'value' => '6', 'disabled' => true],
                        ['key' => 'category', 'value' => 'contracts', 'disabled' => true],
                        ['key' => 'tag', 'value' => 'ijar', 'disabled' => true],
                        ['key' => 'search', 'value' => 'عقد إيجار', 'disabled' => true],
                        ['key' => 'sort', 'value' => 'latest', 'disabled' => true],
                    ],
                    $saveBlogFromList
                ),
                requestItem(
                    'List by category',
                    'GET',
                    '/blogs',
                    'Filter by category slug. Canonical slugs: property-management, contracts, real-estate-market, guides.',
                    false,
                    null,
                    null,
                    [
                        ['key' => 'category', 'value' => 'contracts'],
                        ['key' => 'page', 'value' => '1'],
                    ]
                ),
                requestItem(
                    'List featured (hero)',
                    'GET',
                    '/blogs',
                    'Editor-picked posts (`is_featured=1`). Default per_page is 4. List items also include `is_featured` so the hero can be taken from a normal list.',
                    false,
                    null,
                    null,
                    [
                        ['key' => 'featured', 'value' => '1'],
                    ]
                ),
                requestItem(
                    'Sitemap slim list',
                    'GET',
                    '/blogs',
                    'One-shot sitemap payload. `meta.last_page` is accurate; or use fields=slug,updated_at with a large per_page.',
                    false,
                    null,
                    null,
                    [
                        ['key' => 'per_page', 'value' => '1000'],
                        ['key' => 'fields', 'value' => 'slug,updated_at'],
                    ]
                ),
                requestItem(
                    'Blog taxonomy meta',
                    'GET',
                    '/blogs/meta',
                    'Cached sidebar payload: canonical categories with posts_count, popular tags, marketing stats (إيجار بأرقام).'
                ),
                requestItem(
                    'Show blog by slug',
                    'GET',
                    '/blogs/{{blog_slug}}',
                    'Single post by slug. Increments views_count unless `preview=1` or `X-No-Track: 1`. 404 when unpublished or missing. Includes tags, related_posts, prev/next, sanitized content.'
                ),
                requestItem(
                    'Show blog — preview (no view increment)',
                    'GET',
                    '/blogs/{{blog_slug}}',
                    'ISR / bot revalidation. Does not increment views_count.',
                    false,
                    null,
                    null,
                    [
                        ['key' => 'preview', 'value' => '1'],
                    ]
                ),
                requestItem(
                    'Show blog — not found',
                    'GET',
                    '/blogs/this-slug-does-not-exist',
                    'Expect 404 and `{ message }`.'
                ),
                requestItem(
                    'Subscribe newsletter',
                    'POST',
                    '/newsletter',
                    'Single opt-in, stored locally (no third-party provider / no double opt-in). Duplicate email still returns 200. Invalid email is 422 `{ message, errors.email }`.',
                    false,
                    [
                        'email' => 'user@example.com',
                    ]
                ),
            ],
        ],
        [
            'name' => '2 — SEO login',
            'description' => 'Blog-subdomain CMS login (`seos` table, `seo` guard). Token is a Sanctum personal access token.',
            'item' => [
                requestItem(
                    'SEO login (save token)',
                    'POST',
                    '/seo/login',
                    'Saves `data.token` to `seo_token`. Seed user: seo@email.com / seo123456.',
                    false,
                    [
                        'email' => '{{seo_email}}',
                        'password' => '{{seo_password}}',
                    ],
                    null,
                    [],
                    $saveSeoToken
                ),
            ],
        ],
        [
            'name' => '3 — SEO CMS (write)',
            'description' => 'Requires Bearer `{{seo_token}}`. Image upload is multipart/form-data. Admin panel CRUD is still `/api/admin/blogs`.',
            'item' => [
                requestItem(
                    'Create blog',
                    'POST',
                    '/blogs',
                    'multipart/form-data. `status`: published | draft | scheduled | archived. `scheduled_at` aliases `publish_at`. Saves created id/slug.',
                    true,
                    null,
                    $createForm,
                    [],
                    $saveBlogFromCreate
                ),
                requestItem(
                    'Update blog',
                    'PUT',
                    '/blogs/{{blog_id}}',
                    'multipart/form-data. Numeric id. Optional new image replaces the previous file.',
                    true,
                    null,
                    $updateForm
                ),
                requestItem(
                    'Toggle blog active',
                    'POST',
                    '/blogs/{{blog_id}}/toggle-active',
                    'Flips `is_active`. Inactive posts drop out of the public list.',
                    true
                ),
                requestItem(
                    'Delete blog',
                    'DELETE',
                    '/blogs/{{blog_id}}',
                    'Deletes the post and its stored image. Use a blog created in this collection, not a seeded article.',
                    true
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
