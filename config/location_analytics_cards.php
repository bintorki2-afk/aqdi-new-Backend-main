<?php

/**
 * Dashboard cards for "تحليلات المواقع" (location analytics).
 * Payments are summed via payments.contract_uuid → contracts.uuid → city.
 */
return [
    [
        'key' => 'riyadh',
        'label_ar' => 'الرياض',
        'label_en' => 'Riyadh',
        'location_type' => 'city',
        'city_name_ar' => 'الرياض',
    ],
    [
        'key' => 'jeddah',
        'label_ar' => 'جدة',
        'label_en' => 'Jeddah',
        'location_type' => 'city',
        'city_name_ar' => 'جدة',
    ],
    [
        'key' => 'eastern',
        'label_ar' => 'الشرقية',
        'label_en' => 'Eastern Province',
        'location_type' => 'region',
        'region_name_ar' => 'الشرقية',
    ],
    [
        'key' => 'dammam',
        'label_ar' => 'الدمام',
        'label_en' => 'Dammam',
        'location_type' => 'city',
        'city_name_ar' => 'الدمام',
    ],
];
