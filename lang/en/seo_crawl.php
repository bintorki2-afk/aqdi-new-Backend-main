<?php

return [
    'site' => 'aqdi.sa',
    'title' => 'Technical crawl and site audit',
    'description' => 'Crawls every public page of aqdi.sa and finds technical issues that hurt Google ranking.',

    'indexed_pages' => 'Indexed pages',
    'healthy_pages' => 'Healthy pages',
    'broken_pages' => 'Broken pages/links',
    'on_page_issues' => 'On-page issues',

    'categories' => [
        'duplicate_description' => 'Duplicate descriptions',
        'missing_title' => 'Missing titles',
        'duplicate_title' => 'Duplicate titles',
        'non_indexable' => 'Non-indexable',
        'slow_page' => 'Slow pages',
        'page_404' => '404 error pages',
        'broken_link' => 'Broken links',
        'healthy_pages' => 'Healthy pages',
        'weak_internal_links' => 'Weak internal links',
        'missing_h1' => 'Pages without H1',
        'images_missing_alt' => 'Images without alt text',
        'missing_description' => 'Missing descriptions',
    ],

    'issues' => [
        'missing_title' => 'Missing page title',
        'duplicate_title' => 'Duplicate page title with :path',
        'missing_description' => 'Missing meta description',
        'duplicate_description' => 'Duplicate meta description',
        'missing_h1' => 'No main H1 heading',
        'non_indexable' => 'Page is non-indexable',
        'page_404' => '404 error page',
        'broken_link' => 'Broken internal link (404)',
        'slow_page' => 'Slow loading time (:seconds s)',
        'weak_internal_links' => 'Weak internal links',
        'images_missing_alt' => 'Images without alt text (:count images)',
    ],
];
