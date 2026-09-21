<?php

/**
 * Employee KPI dashboard (per-employee card).
 */
return [
    'receive_sla_minutes' => 5,
    'late_after_hours' => 24,
    'score_weights' => [
        'receive_speed_on_duty' => 0.40,
        'processing_commitment' => 0.40,
        'completion_volume' => 0.20,
    ],
    'default_shift' => [
        'name' => 'وردية الصباح',
        'start' => '09:00',
        'end' => '17:00',
    ],
    'work_periods' => [
        'morning' => [
            'value' => 'morning',
            'label_ar' => 'صباحا',
            'label_en' => 'Morning',
            'shift_name' => 'وردية الصباح',
            'start' => '09:00',
            'end' => '17:00',
        ],
        'evening' => [
            'value' => 'evening',
            'label_ar' => 'مساء',
            'label_en' => 'Evening',
            'shift_name' => 'وردية المساء',
            'start' => '17:00',
            'end' => '01:00',
        ],
    ],
    'activity_limit' => 20,
    'activity_full_limit' => 2000,
];
