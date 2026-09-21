<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canonical public categories (blogs.aqdi.sa tabs)
    |--------------------------------------------------------------------------
    |
    | Slugs are stable. Labels are localized via Accept-Language / ?locale=.
    | "All" is a front-end tab (no slug). Do not rename slugs without coordinating
    | with the blog front-end.
    |
    */
    'categories' => [
        [
            'slug' => 'property-management',
            'label_ar' => 'إدارة العقارات',
            'label_en' => 'Property management',
        ],
        [
            'slug' => 'contracts',
            'label_ar' => 'العقود',
            'label_en' => 'Contracts',
        ],
        [
            'slug' => 'real-estate-market',
            'label_ar' => 'سوق العقارات',
            'label_en' => 'Real estate market',
        ],
        [
            'slug' => 'guides',
            'label_ar' => 'أدلة',
            'label_en' => 'Guides',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | "إيجار بأرقام" sidebar stats
    |--------------------------------------------------------------------------
    |
    | Marketing-fixed copy, not live analytics. Override via BLOG_STATS_* env.
    |
    */
    'stats' => [
        'active_users' => env('BLOG_STATS_ACTIVE_USERS', '+5M'),
        'active_contracts' => env('BLOG_STATS_ACTIVE_CONTRACTS', '+2M'),
        'leased_units' => env('BLOG_STATS_LEASED_UNITS', '+3M'),
    ],

    'words_per_minute' => (int) env('BLOG_WORDS_PER_MINUTE', 200),

    'excerpt_length' => 160,

    'list_per_page' => 6,

    'list_per_page_max' => 1000,

    'featured_max' => 4,

    'related_posts_max' => 3,

    'popular_tags_limit' => 12,

    'cache' => [
        'list' => 'public, max-age=60, s-maxage=300, stale-while-revalidate=300',
        'show' => 'public, max-age=60, s-maxage=300, stale-while-revalidate=300',
        'meta' => 'public, max-age=60, s-maxage=300, stale-while-revalidate=300',
        'meta_ttl' => 300,
    ],

    'default_author' => 'فريق عقدي',

];
