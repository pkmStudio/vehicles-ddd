<?php

declare(strict_types=1);

return [
    'external' => [
        'cache' => [
            'ttl_seconds' => 86400,

            'keys' => [
                'accepted' => 'applicability_external_import_accepted_%s',
                'cleanup' => 'applicability_external_import_cleanup_%s',
            ],
        ],
    ],

    'failures' => [
        'disk' => env('IMPORT_FAILURES_REPORT_DISK', 'local'),

        'cache' => [
            'keys' => [
                'kit_applicability_import_failures' => 'kit_applicability_import_failures_%s',
                'kit_applicability_import_failures_lock' => 'kit_applicability_import_failures_lock_%s',
            ],
        ],
    ],
];
