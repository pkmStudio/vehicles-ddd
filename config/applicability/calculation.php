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

    'runtime' => [
        'cache' => [
            'ttl_seconds' => 86400,

            'keys' => [
                'run' => 'applicability_calculation_run_%s',
                'chunk_finished' => 'applicability_calculation_chunk_finished_%s_%d',
                'finalization' => 'applicability_calculation_finalization_%s',
                'lock' => 'applicability_calculation_lock_%s',
            ],
        ],
    ],

    'failures' => [
        'disk' => env('APPLICABILITY_CALCULATION_FAILURES_REPORT_DISK', 'exports'),
        'directory' => 'exports',
    ],
];
