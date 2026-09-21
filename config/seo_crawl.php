<?php

return [
    'base_url' => env('SEO_CRAWL_BASE_URL', 'https://aqdi.sa'),

    'user_agent' => env('SEO_CRAWL_USER_AGENT', 'AqdiSeoBot/1.0 (+https://aqdi.sa)'),

    'max_pages' => (int) env('SEO_CRAWL_MAX_PAGES', 400),

    'timeout_seconds' => (int) env('SEO_CRAWL_TIMEOUT', 20),

    'delay_ms' => (int) env('SEO_CRAWL_DELAY_MS', 80),

    /** Pages slower than this (milliseconds) are flagged as slow. */
    'slow_page_ms' => (int) env('SEO_CRAWL_SLOW_MS', 3000),

    /** HTML pages with fewer inbound internal links than this are "weak". Home is exempt. */
    'weak_inbound_links' => (int) env('SEO_CRAWL_WEAK_INBOUND', 2),

    'allowed_hosts' => [
        'aqdi.sa',
        'www.aqdi.sa',
        'blogs.aqdi.sa',
    ],

    /** Additional public sites/subdomains included in every crawl. */
    'seed_urls' => [
        'https://blogs.aqdi.sa',
    ],

    /** Collapse only true aliases; preserve distinct subdomains such as blogs.aqdi.sa. */
    'canonical_hosts' => [
        'www.aqdi.sa' => 'aqdi.sa',
    ],

    'exclude_path_regex' => [
        '#^/(login|signup|logout|forget_password|new_password|reset_password|send_code|verification|resend-verification)(/|$)#i',
        '#^/(mycontract(?:test)?|real-estate)(/|$)#i',
        '#^/(?:new|step[123]|end|show|edit|delete)/realestate(/|$)#i',
        '#^/contract(/|$)#i',
        '#^/(work-paper|financial_statements)/#i',
        '#^/pricing/[A-Za-z0-9\-]+#i',
        '#^/(real|unit|choose|coupon|check)(/|$)#i',
        '#^/(api|admin|telescope|livewire|storage)(/|$)#i',
        '#^/db(/|$)#i',
    ],

    /** Write live crawl status to Firebase Realtime Database. */
    'firebase_status' => filter_var(env('SEO_CRAWL_FIREBASE_STATUS', true), FILTER_VALIDATE_BOOLEAN),

    'firebase_path' => env('SEO_CRAWL_FIREBASE_PATH', 'seo_crawl/status'),

    /** Minimum seconds between progress writes while a crawl is running. */
    'firebase_progress_interval' => (float) env('SEO_CRAWL_FIREBASE_PROGRESS_INTERVAL', 1),

    'skip_extensions' => [
        'pdf', 'zip', 'rar', '7z', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'mp3', 'mp4', 'avi', 'mov', 'wmv', 'webp', 'jpg', 'jpeg', 'png', 'gif',
        'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'css', 'js', 'map', 'xml',
        'json', 'txt', 'csv',
    ],
];
