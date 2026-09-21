<?php

/**
 * Dashboard metric definitions — keys are English slugs only (never Arabic).
 * Arabic/English labels are for display (label_ar / label_en).
 */
return [
    'user_activity_rate' => [
        'label_ar' => 'معدل نشاط المستخدمين',
        'label_en' => 'User activity rate',
        'type' => 'percentage',
    ],
    'most_clients_completed_requests' => [
        'label_ar' => 'أكثر العملاء طلب مكتمل',
        'label_en' => 'Top customers — completed orders',
        'type' => 'count',
    ],
    'most_clients_incomplete_requests' => [
        'label_ar' => 'أكثر العملاء طلب غير مكتمل',
        'label_en' => 'Top customers — incomplete orders',
        'type' => 'count',
    ],
    'most_clients_requests' => [
        'label_ar' => 'أكثر العملاء طلبات',
        'label_en' => 'Top customers — total orders',
        'type' => 'count',
    ],
    'most_clients_returns' => [
        'label_ar' => 'أكثر العملاء استرجاع',
        'label_en' => 'Top customers — refunds',
        'type' => 'count',
    ],
    'most_clients_real_estate' => [
        'label_ar' => 'أكثر العملاء عقارات',
        'label_en' => 'Top customers — real estate',
        'type' => 'count',
    ],
    'most_clients_units' => [
        'label_ar' => 'أكثر العملاء وحدات',
        'label_en' => 'Top customers — units',
        'type' => 'count',
    ],
];
