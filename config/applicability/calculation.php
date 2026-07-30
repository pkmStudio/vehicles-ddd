<?php

declare(strict_types=1);

return [
    'external' => [
        'cache' => [
            'ttl_seconds' => 86400,

            'keys' => [
                'user_id' => 'applicability_external_calculation_user_%s',
            ],
        ],
    ],

    'failures' => [
        'disk' => env('APPLICABILITY_CALCULATION_FAILURES_REPORT_DISK', 'exports'),
        'directory' => 'exports',
    ],
];
